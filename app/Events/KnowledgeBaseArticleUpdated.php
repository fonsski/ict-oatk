<?php

namespace App\Events;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KnowledgeBaseArticleUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public KnowledgeBase $article;
    public ?User $user;

    
     * Create a new event instance.

    public function __construct(KnowledgeBase $article, ?User $user = null)
    {
        $this->article = $article;
        $this->user = $user;
    }
}
