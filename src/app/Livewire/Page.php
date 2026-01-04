<?php

namespace App\Livewire;

use App\Services\PageService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Page extends Component
{
    #[Locked]
    public string $pageName = '';

    protected PageService $pageService;

    public function boot()
    {
        $this->pageService = app(PageService::class);
    }

    public function mount($pageName)
    {
        $this->pageName = $pageName;
    }

    public function render()
    {
        $content = $this->pageService->getPage($this->pageName);

        if (!$content) {
            abort(404);
        }

        return view('livewire.page', [
            'content' => $content,
            'pageName' => $this->pageName,
        ]);
    }
}
