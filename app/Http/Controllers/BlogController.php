<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    private function normalizeText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        if (! function_exists('mb_detect_encoding')) {
            return $text;
        }

        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        if ($encoding === false) {
            return mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        return $text;
    }

    private function categoryOptions(): array
    {
        return [
            1 => 'Tecnologia',
            2 => 'Viajes',
            3 => 'Cocina',
            4 => 'Salud y bienestar',
            5 => 'Negocios',
            6 => 'Desarrollo personal',
            7 => 'Otros',
        ];
    }

    private function statusOptions(): array
    {
        return [
            1 => 'Publicado',
            0 => 'Borrador',
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && (int) $user->user_level_id === 1;
        $userLevelName = $user ? (UserLevel::find($user->user_level_id)?->name ?? 'Usuario') : 'Usuario';

        $posts = BlogPost::query()->orderByDesc('id')->paginate(9);
        $categories = $this->categoryOptions();
        $statuses = $this->statusOptions();

        $posts->getCollection()->transform(function (BlogPost $post) use ($categories, $statuses) {
            return [
                'id' => $post->id,
                'title' => $this->normalizeText($post->title) ?: 'Sin titulo',
                'summary' => $this->normalizeText($post->summary) ?: 'Sin resumen.',
                'slug' => $post->slug,
                'image' => $post->featured_image,
                'category_label' => $categories[$post->blog_post_category_id] ?? 'Sin categoria',
                'status_label' => $statuses[$post->status] ?? 'Sin estado',
                'updated_at' => $post->updated_at ? $post->updated_at->format('d/m/Y') : '',
            ];
        });

        return view('post.blogs', [
            'user' => $user,
            'userLevelName' => $userLevelName,
            'isAdmin' => $isAdmin,
            'activeNav' => 'blog',
            'posts' => $posts,
        ]);
    }

    public function createBlog()
    {
        $user = Auth::user();
        $isAdmin = $user && (int) $user->user_level_id === 1;
        $userLevelName = $user ? (UserLevel::find($user->user_level_id)?->name ?? 'Usuario') : 'Usuario';

        return view('post.blog_create', [
            'user' => $user,
            'userLevelName' => $userLevelName,
            'isAdmin' => $isAdmin,
            'activeNav' => 'blog',
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function edit(string $id)
    {
        $user = Auth::user();
        if (! $user || (int) $user->user_level_id !== 1) {
            abort(403);
        }

        $post = BlogPost::find($id);
        if (! $post) {
            abort(404);
        }

        $postData = [
            'id' => $post->id,
            'title' => $this->normalizeText($post->title),
            'slug' => $post->slug,
            'summary' => $this->normalizeText($post->summary),
            'content' => $this->normalizeText($post->content),
            'featured_image' => $post->featured_image,
            'category_id' => $post->blog_post_category_id,
        ];

        return view('post.blog_edit', [
            'user' => $user,
            'userLevelName' => UserLevel::find($user->user_level_id)?->name ?? 'Usuario',
            'isAdmin' => true,
            'activeNav' => 'blog',
            'categories' => $this->categoryOptions(),
            'post' => $postData,
        ]);
    }

    public function saveArticle(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'summary' => 'required|string|max:255',
            'content' => 'required|string',
            'featured_image' => 'required|image|mimes:jpg,jpeg,png,webp,gif',
            'category' => 'nullable',
        ]);

        $slug = Str::slug($validated['slug'] ?: $validated['title']);

        if (BlogPost::where('slug', $slug)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'El slug ya existe, prueba con otro.',
            ], 422);
        }

        $categoryId = (int) ($validated['category'] ?? 0);
        $categoryOptions = $this->categoryOptions();
        if (! array_key_exists($categoryId, $categoryOptions)) {
            $categoryId = 0;
        }

        $imagePath = '';
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = time().'_'.Str::random(12).'.'.$extension;
            $destination = public_path('img/article');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $fileName);
            $imagePath = 'img/article/'.$fileName;
        }

        $post = BlogPost::create([
            'title' => trim($this->normalizeText($validated['title'])),
            'slug' => $slug,
            'summary' => trim($this->normalizeText($validated['summary'])),
            'content' => trim($this->normalizeText($validated['content'])),
            'featured_image' => $imagePath,
            'status' => 1,
            'blog_post_category_id' => $categoryId,
        ]);

        return response()->json([
            'status' => 'success',
            'id' => $post->id,
        ]);
    }

    public function showAll()
    {
        $posts = BlogPost::query()
            ->where('status', 1)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        $posts->getCollection()->transform(function (BlogPost $post) {
            return [
                'id' => $post->id,
                'title' => $this->normalizeText($post->title) ?: 'Sin titulo',
                'summary' => $this->normalizeText($post->summary) ?: 'Sin resumen.',
                'slug' => $post->slug,
                'image' => $post->featured_image,
            ];
        });

        return view('page.blogs', [
            'posts' => $posts,
        ]);
    }

    public function showArticle(string $slug)
    {
        $article = BlogPost::query()
            ->where('slug', $slug)
            ->where('status', 1)
            ->first();

        if (! $article) {
            abort(404);
        }

        $article->title = $this->normalizeText($article->title);
        $article->summary = $this->normalizeText($article->summary);
        $article->content = $this->normalizeText($article->content);

        return view('page.article', [
            'article' => $article,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user || (int) $user->user_level_id !== 1) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado'], 403);
        }

        $post = BlogPost::find($id);
        if (! $post) {
            return response()->json(['status' => 'error', 'message' => 'Articulo no encontrado'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'summary' => 'required|string|max:255',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif',
            'category' => 'nullable',
        ]);

        $slug = Str::slug($validated['slug'] ?: $validated['title']);

        if (BlogPost::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'El slug ya existe, prueba con otro.',
            ], 422);
        }

        $categoryId = (int) ($validated['category'] ?? 0);
        $categoryOptions = $this->categoryOptions();
        if (! array_key_exists($categoryId, $categoryOptions)) {
            $categoryId = 0;
        }

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image) {
                $oldPath = public_path($post->featured_image);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $file = $request->file('featured_image');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = time().'_'.Str::random(12).'.'.$extension;
            $destination = public_path('img/article');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $fileName);
            $post->featured_image = 'img/article/'.$fileName;
        }

        $post->title = trim($this->normalizeText($validated['title']));
        $post->slug = $slug;
        $post->summary = trim($this->normalizeText($validated['summary']));
        $post->content = trim($this->normalizeText($validated['content']));
        $post->blog_post_category_id = $categoryId;
        $post->save();

        return response()->json(['status' => 'success']);
    }

    public function delete(Request $request)
    {
        $user = Auth::user();
        if (! $user || (int) $user->user_level_id !== 1) {
            return response()->json(['status' => 403]);
        }

        $postId = (int) $request->query('id');
        $post = BlogPost::find($postId);
        if (! $post) {
            return response()->json(['status' => 404]);
        }

        if ($post->featured_image) {
            $path = public_path($post->featured_image);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $post->delete();

        return response()->json(['status' => 200]);
    }
}
