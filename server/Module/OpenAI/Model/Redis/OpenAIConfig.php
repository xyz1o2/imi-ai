<?php

declare(strict_types=1);

namespace app\Module\OpenAI\Model\Redis;

use app\Module\Config\Annotation\ConfigModel;
use app\Module\Config\Model\Redis\Traits\TConfigModel;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\Entity;
use Imi\Model\Annotation\JsonDecode;
use Imi\Model\Annotation\RedisEntity;
use Imi\Model\Annotation\Serializables;
use Imi\Model\RedisModel;

#[
    Entity(),
    RedisEntity(key: 'config:openai', storage: 'hash_object'),
    ConfigModel(title: 'OpenAI设置'),
    Serializables(mode: 'allow'),
]
class OpenAIConfig extends RedisModel
{
    use TConfigModel;

    /**
     * @var Api[]
     */
    #[
        Column(type: 'json'),
        JsonDecode(wrap: Api::class, arrayWrap: true),
    ]
    protected array $apis = [];

    /**
     * @return Api[]
     */
    public function getApis(): array
    {
        return $this->apis;
    }

    /**
     * @param Api[] $apis
     */
    public function setApis(array $apis): self
    {
        $this->apis = $apis;

        return $this;
    }

    public function getRandomApi(?string $model = null): Api
    {
        $apis = $this->apis;
        foreach ($apis as $i => $api)
        {
            if (!$api->enable
            || (null !== $model && $api->models && !\in_array($model, $api->models))
            ) {
                unset($apis[$i]);
            }
        }
        while ($apis)
        {
            $key = array_rand($apis);
            $api = $apis[$key];
            if ($api->isRateLimit())
            {
                unset($apis[$key]);
            }
            else
            {
                return $api;
            }
        }
        throw new \RuntimeException('没有可用的 API');
    }
}
