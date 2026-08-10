<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SeedWorkspaceTemplates;
use App\Models\Template;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class TemplateController extends Controller
{
    public function index(WorkspaceContext $context, SeedWorkspaceTemplates $seeder): View
    {
        $seeder->execute($context->get());
        $templates = Template::query()->with('versions')->where('workspace_id', $context->id())->orderBy('name')->get();

        return view('templates.index', compact('templates'));
    }

    public function preview(Template $template, WorkspaceContext $context): View
    {
        $this->guard($template, $context);
        $template->load('versions');

        return view('templates.preview', compact('template'));
    }

    public function duplicate(Request $request, Template $template, WorkspaceContext $context): RedirectResponse
    {
        $this->guard($template, $context);
        $request->validate(['name' => ['nullable', 'string', 'max:120']]);
        DB::transaction(function () use ($request, $template, $context): void {
            $name = $request->string('name')->trim()->toString() ?: $template->name.' Copy';
            $suffix = 2;
            while (Template::query()->where('workspace_id', $context->id())->where('name', $name)->exists()) {
                $name = $template->name.' Copy '.$suffix++;
            }
            $copy = Template::query()->create(['workspace_id' => $context->id(), 'name' => $name, 'style' => $template->style, 'is_active' => true]);
            foreach ($template->versions as $version) {
                $copy->versions()->create(['workspace_id' => $context->id(), 'version' => $version->version, 'content' => $version->content]);
            }
        });

        return back()->with('status', 'Template duplicated.');
    }

    private function guard(Template $template, WorkspaceContext $context): void
    {
        abort_unless($template->workspace_id === $context->id(), 404);
    }
}
