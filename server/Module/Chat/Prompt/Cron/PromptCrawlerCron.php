<?php

declare(strict_types=1);

namespace app\Module\Chat\Prompt\Cron;

use app\Module\Chat\Prompt\PromptCrawler;
use Imi\App;
use Imi\Cron\Annotation\Cron;
use Imi\Cron\Contract\ICronTask;

#[
    Cron(id: PromptCrawlerCron::class, second: '1h', type: 'task')
]
class PromptCrawlerCron implements ICronTask
{
    /**
     * 执行任务
     *
     * @param mixed $data
     */
    public function run(string $id, $data): void
    {
        $promptCrawler = App::getBean(PromptCrawler::class);
        $promptCrawler->crawl();
    }
}
