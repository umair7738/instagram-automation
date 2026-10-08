<?php

namespace App\Support;

class StarterExamples
{
    public function templates(): array
    {
        return [
            [
                'key' => 'resource-link',
                'name' => 'Send a resource link',
                'description' => 'A friendly private reply that includes the selected link resource.',
                'body' => 'Hi {first_name}, here is the link you asked for: {resource_url}',
            ],
            [
                'key' => 'welcome',
                'name' => 'Welcome message',
                'description' => 'A simple acknowledgement for a new conversation.',
                'body' => 'Hi {first_name}, thanks for reaching out. How can we help?',
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [
                'key' => 'comment-info',
                'name' => 'Send details when someone comments INFO',
                'description' => 'Matches the word INFO, posts a public acknowledgement, and prepares a private link reply.',
                'keyword' => 'INFO',
                'public_reply' => 'Thanks! I have sent you the details.',
                'template' => $this->templates()[0],
            ],
            [
                'key' => 'comment-price',
                'name' => 'Answer PRICE comments',
                'description' => 'Matches PRICE comments and creates a paused public-reply draft.',
                'keyword' => 'PRICE',
                'public_reply' => 'Thanks for asking. We will send you the latest details shortly.',
                'template' => null,
            ],
        ];
    }

    public function template(string $key): ?array
    {
        return collect($this->templates())->firstWhere('key', $key);
    }

    public function rule(string $key): ?array
    {
        return collect($this->rules())->firstWhere('key', $key);
    }
}
