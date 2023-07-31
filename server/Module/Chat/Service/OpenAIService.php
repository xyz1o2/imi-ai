<?php

declare(strict_types=1);

namespace app\Module\Chat\Service;

use app\Exception\NotFoundException;
use app\Module\Business\Enum\BusinessType;
use app\Module\Card\Service\MemberCardService;
use app\Module\Chat\Enum\QAStatus;
use app\Module\Chat\Model\ChatMessage;
use app\Module\Chat\Model\ChatSession;
use app\Module\Chat\Model\Redis\ChatConfig;
use app\Module\Chat\Util\Gpt3Tokenizer;
use app\Module\Chat\Util\OpenAI;
use app\Util\TokensUtil;
use Imi\Aop\Annotation\Inject;
use Imi\Db\Annotation\Transaction;
use Imi\Log\Log;
use Imi\Util\ObjectArrayHelper;
use Imi\Validate\Annotation\AutoValidation;
use Imi\Validate\Annotation\Text;

use function Yurun\Swoole\Coroutine\goWait;

class OpenAIService
{
    public const ALLOW_PARAMS = ['model', 'temperature', 'top_p',  'presence_penalty', 'frequency_penalty'];

    #[Inject()]
    protected MemberCardService $memberCardService;

    #[
        Transaction(),
        AutoValidation(),
        Text(name: 'message', min: 1, message: '内容不能为空'),
    ]
    public function sendMessage(string $message, string $id, int $memberId, string $ip = '', array|object $config = []): ChatSession
    {
        $tokens = \count(Gpt3Tokenizer::getInstance()->encode($message));

        // 检查余额
        $this->memberCardService->checkBalance($memberId, $tokens + 1);

        if ([] === $config)
        {
            $config = new \stdClass();
        }

        $chatConfig = ChatConfig::__getConfigAsync();
        $model = ObjectArrayHelper::get($config, 'model', 'gpt-3.5-turbo');
        $modelConfig = $chatConfig->getModelConfig()[$model] ?? null;
        if (!$modelConfig || !$modelConfig->enable)
        {
            throw new \RuntimeException('不允许使用模型：' . $model);
        }

        if ('' === $id)
        {
            $record = $this->create($memberId, mb_substr($message, 0, 16), $config, $ip);
        }
        else
        {
            $record = $this->getByIdStr($id, $memberId);
            if (QAStatus::ASK !== $record->qaStatus)
            {
                throw new \RuntimeException('当前状态不允许提问');
            }
            // 更新记录
            $record->config = $config;
            $record->qaStatus = QAStatus::ANSWER;
            $record->update();
        }

        $this->appendMessage($record->id, 'user', [], $tokens, $message, $record->updateTime, $record->updateTime, $ip);

        return $record;
    }

    public function chatStream(string $id, int $memberId, string $ip): \Iterator
    {
        /** @var ChatSession $record */
        $record = goWait(fn () => $this->getByIdStr($id, $memberId), 30, true);
        if (QAStatus::ANSWER !== $record->qaStatus)
        {
            throw new \RuntimeException('AI 已回答完毕');
        }
        $gpt3Tokenizer = Gpt3Tokenizer::getInstance();
        $inputTokens = 0;
        $messages = [];
        foreach (goWait(fn () => $this->selectMessages($record->id, 'asc'), 30, true) as $message)
        {
            /** @var ChatMessage $message */
            $messages[] = ['role' => $message->role, 'content' => $message->message];
            $inputTokens += $gpt3Tokenizer->count($message->message);
        }
        if (!$messages)
        {
            throw new \RuntimeException('没有消息');
        }
        $record->tokens += $inputTokens;
        $config = ChatConfig::__getConfigAsync();
        $client = OpenAI::makeClient();
        $beginTime = time();
        $params = [];
        foreach (self::ALLOW_PARAMS as $name)
        {
            if (isset($record->config[$name]))
            {
                $params[$name] = $record->config[$name];
            }
        }
        $params['model'] ??= 'gpt-3.5-turbo';
        $modelConfig = $config->getModelConfig()[$params['model']] ?? null;
        if (!$modelConfig || !$modelConfig->enable)
        {
            throw new \RuntimeException('不允许使用模型：' . $params['model']);
        }
        $model = $params['model'];
        $params['messages'] = $messages;
        // @phpstan-ignore-next-line
        $stream = $client->chat()->createStreamed($params);
        goWait(static fn () => $record->update(), 30, true);
        $role = null;
        $content = '';
        $finishReason = null;
        foreach ($stream as $response)
        {
            $data = $response->choices[0]->toArray();
            $delta = $data['delta'];
            if (isset($delta['role']))
            {
                $role = $delta['role'];
            }
            else
            {
                $yieldData = [];
                if ($finishReason = ($data['finish_reason'] ?? null))
                {
                    $yieldData['finishReason'] = $finishReason;
                }
                if (isset($delta['content']))
                {
                    $content .= $delta['content'];
                    $yieldData['content'] = $delta['content'];
                }
                if (!$yieldData)
                {
                    Log::error('Unknown response: ' . json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT));
                    continue;
                }
                yield $yieldData;
            }
        }
        $endTime = time();
        $outputTokens = $gpt3Tokenizer->count($content);
        [$payInputTokens, $payOutputTokens] = TokensUtil::calcDeductToken($model, $inputTokens, $outputTokens, $config->getModelConfig());
        $this->appendMessage($record->id, $role, $record->config, $outputTokens, $content, $beginTime, $endTime, $ip);
        $record = $this->getById($record->id);
        $record->tokens += $outputTokens;
        $record->payTokens += ($payTokens = $payInputTokens + $payOutputTokens);
        $record->qaStatus = QAStatus::ASK;
        $record->update();

        // 扣款
        $this->memberCardService->pay($record->memberId, $payTokens, BusinessType::CHAT, $record->id, time: $endTime);
    }

    public function getById(int $id, int $memberId = 0): ChatSession
    {
        $record = ChatSession::find($id);
        if (!$record || ($memberId && $record->memberId !== $memberId))
        {
            throw new NotFoundException(sprintf('会话 %s 不存在', $id));
        }

        return $record;
    }

    public function getByIdStr(string $id, int $memberId = 0): ChatSession
    {
        $intId = ChatSession::decodeId($id);
        $record = ChatSession::find($intId);
        if (!$record || ($memberId && $record->memberId !== $memberId))
        {
            throw new NotFoundException(sprintf('会话 %s 不存在', $id));
        }

        return $record;
    }

    public function create(int $memberId, string $title, array|object $config, string $ip = ''): ChatSession
    {
        $record = ChatSession::newInstance();
        $record->memberId = $memberId;
        $record->title = $title;
        $record->config = $config;
        $record->qaStatus = QAStatus::ANSWER;
        $record->ipData = inet_pton($ip) ?: '';
        $record->insert();

        return $record;
    }

    public function list(int $memberId, int $page = 1, int $limit = 15): array
    {
        $query = ChatSession::query();
        if ($memberId)
        {
            $query->where('member_id', '=', $memberId);
        }

        return $query->order('update_time', 'desc')
                     ->paginate($page, $limit)
                     ->toArray();
    }

    public function edit(string $id, ?string $title, array|object|null $config, int $memberId): void
    {
        $record = $this->getByIdStr($id, $memberId);
        if (null !== $title)
        {
            $record->title = $title;
        }
        if (null !== $config)
        {
            $record->config = [] === $config ? new \stdClass() : $config;
        }
        $record->update();
    }

    public function delete(string $id, int $memberId = 0): void
    {
        $record = $this->getByIdStr($id, $memberId);
        $record->delete();
    }

    public function appendMessage(int $sessionId, string $role, array|object $config, int $tokens, string $message, int $beginTime, int $completeTime, string $ip): ChatMessage
    {
        $record = ChatMessage::newInstance();
        $record->sessionId = $sessionId;
        $record->role = $role;
        $record->config = [] === $config ? new \stdClass() : $config;
        $record->tokens = $tokens;
        $record->message = $message;
        $record->beginTime = $beginTime;
        $record->completeTime = $completeTime;
        $record->ipData = inet_pton($ip) ?: '';
        $record->insert();

        return $record;
    }

    /**
     * @return ChatMessage[]
     */
    public function selectMessagesIdStr(string $sessionId, string $sort = 'asc'): array
    {
        $id = ChatSession::decodeId($sessionId);

        return $this->selectMessages($id, $sort);
    }

    /**
     * @return ChatMessage[]
     */
    public function selectMessages(int $sessionId, string $sort = 'asc'): array
    {
        return ChatMessage::query()->where('session_id', '=', $sessionId)
                                   ->order('id', $sort)
                                   ->select()
                                   ->getArray();
    }
}
