<?php

namespace App\Listeners;

use App\Events\KnowledgeBaseArticleUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogKnowledgeBaseArticleUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    
     * Create the event listener.

    public function __construct()
    {
        
    }

    
     * Handle the event.

    public function handle(KnowledgeBaseArticleUpdated $event): void
    {
        $article = $event->article;
        $user = $event->user;
        
        Log::info('Knowledge base article updated', [
            'article_id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'category_id' => $article->category_id,
            'author_id' => $article->author_id,
            'updated_by' => $user ? $user->id : null,
            'updated_by_name' => $user ? $user->name : 'Unknown',
            'updated_at' => $article->updated_at->toISOString(),
        ]);
    }
}
