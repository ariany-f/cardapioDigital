<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Languages/Index', [
            'languages' => Language::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'name' => ['required', 'string', 'max:100'],
            'flag' => ['nullable', 'string', 'max:16'],
        ]);

        Language::create([...$data, 'is_active' => true]);

        return back()->with('success', 'Idioma cadastrado.');
    }

    public function exportTemplate(): StreamedResponse
    {
        $path = lang_path('pt_BR.json');

        return response()->streamDownload(
            fn () => print (File::get($path)),
            'translations-template-pt_BR.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10'],
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain'],
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $json = json_decode($content, true);

        if (! is_array($json)) {
            return back()->withErrors(['file' => 'JSON inválido.']);
        }

        $target = lang_path($data['code'].'.json');
        File::put($target, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Language::query()->updateOrCreate(
            ['code' => $data['code']],
            ['name' => $data['code'], 'flag' => '🌐', 'is_active' => true]
        );

        return back()->with('success', 'Traduções importadas.');
    }
}
