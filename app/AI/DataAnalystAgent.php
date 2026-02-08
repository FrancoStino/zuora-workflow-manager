<?php

namespace App\AI;

use App\Services\ModelsDevService;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;
use NeuronAI\Agent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAILike;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSchemaTool;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSelectTool;
use PDO;

class DataAnalystAgent extends Agent
{
    public function __construct(protected ?PDO $pdo = null)
    {
        $this->pdo ??= DB::connection()->getPdo();
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a data analyst. Analyze database queries and provide insights.',
            ],
        );
    }

    protected function provider(): AIProviderInterface
    {
        $settings      = app(GeneralSettings::class);
        $modelsService = app(ModelsDevService::class);

        $baseUri = $modelsService->getApiEndpoint($settings->aiProvider);

        return new OpenAILike(
            baseUri: $baseUri,
            key: $settings->aiApiKey,
            model: $settings->aiModel,
            httpOptions: new HttpClientOptions(timeout: 120),
        );
    }

    protected function chatHistory(): ChatHistoryInterface
    {
        return new InMemoryChatHistory(contextWindow: 200000);
    }

    protected function tools(): array
    {
        return [
            MySQLSchemaTool::make($this->pdo),
            MySQLSelectTool::make($this->pdo),
        ];
    }
}
