<?php

namespace App\Listeners;

use App\Events\KnowledgeBaseArticleCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogKnowledgeBaseArticleCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(KnowledgeBaseArticleCreated $event): void
    {
        $article = $event->article;
        $user = $event->user;
        
        Log::info('Knowledge base article created', [
            'article_id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'category_id' => $article->category_id,
            'author_id' => $article->author_id,
            'author_name' => $user ? $user->name : 'Unknown',
            'created_at' => $article->created_at->toISOString(),
        ]);
    }
}
