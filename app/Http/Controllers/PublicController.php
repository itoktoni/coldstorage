<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Type;
use App\Models\Menu;
use App\Services\ContentEntryExtractor;
use Illuminate\Http\Request;


class PublicController extends Controller
{
    public function api($slug = null)
    {
        $query = Content::with('type.sections')->where('status', 'published');

        if ($slug) {
            $entry = (clone $query)->where('slug', $slug)->firstOrFail();
        } else {
            $entry = (clone $query)->whereHas('type', fn($q) => $q->where('slug', 'homepage'))
                ->firstOrFail();
        }

        $parsing = response()->json(ContentEntryExtractor::extract($entry));

        return $parsing;
    }

    public function index()
    {

        if(env('DISABLE_FRONTEND', false))
        {
            return redirect()->route('login');
        }

        $menu = Menu::getByLocation('main');
        $contentTypes = Type::where('is_active', true)->get();

        $homeEntry = Content::with(['type.sections'])
            ->whereHas('type', fn($q) => $q->where('slug', 'homepage'))
            ->where('status', 'published')
            ->first();

        $homeData = null;
        if ($homeEntry) {
            $homeData = ContentEntryExtractor::extract($homeEntry);
        }

        return view('frontend.index', [
            'menu' => $menu,
            'contentTypes' => $contentTypes,
            'homeEntry' => $homeEntry,
            'homeData' => $homeData,
        ]);
    }

    public function page($slug)
    {
        $menu = Menu::getByLocation('main');
        $entry = Content::with('type')->whereHas('type', function ($q) {
            $q->where('slug', 'page');
        })
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('frontend.page', ['entry' => $entry, 'menu' => $menu]);
    }

    public function blog()
    {
        $posts = Content::with('type')->whereHas('type', function ($q) {
            $q->where('slug', 'post');
        })
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('frontend.blog', ['posts' => $posts]);
    }

    public function post($slug)
    {
        $post = Content::with('type')->whereHas('type', function ($q) {
            $q->where('slug', 'post');
        })
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('frontend.post', ['entry' => $post]);
    }

    public function category($slug)
    {
        $category = \App\Models\Category::where('slug', $slug)->firstOrFail();

        $posts = Content::with('type')->whereHas('type', function ($q) {
            $q->where('slug', 'post');
        })
            ->where('status', 'published')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('frontend.blog', [
            'posts' => $posts,
            'category' => $category,
        ]);
    }

    public function tag($slug)
    {
        $tag = \App\Models\Tag::where('slug', $slug)->firstOrFail();

        $posts = Content::with('type')->whereHas('type', function ($q) {
            $q->where('slug', 'post');
        })
            ->where('status', 'published')
            ->whereHas('tags', function ($q) use ($tag) {
                $q->where('tags.id', $tag->id);
            })
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('frontend.blog', [
            'posts' => $posts,
            'tag' => $tag,
        ]);
    }

    public function services()
    {
        $entry = Content::with('type.sections')
            ->whereHas('type', fn($q) => $q->where('slug', 'services'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();

        $data = null;
        if ($entry) {
            $data = ContentEntryExtractor::extract($entry);
        }

        return view('frontend.services', [
            'entry' => $entry,
            'data' => $data,
        ]);
    }

    public function contact()
    {
        $entry = Content::with('type.sections')
            ->whereHas('type', fn($q) => $q->where('slug', 'contact'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();

        $data = null;
        if ($entry) {
            $data = ContentEntryExtractor::extract($entry);
        }

        return view('frontend.contact', [
            'entry' => $entry,
            'data' => $data,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');

        $posts = Content::with('type')
            ->whereHas('type', fn($q) => $q->where('slug', 'post'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        if ($query) {
            $posts->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%')
                  ->orWhere('excerpt', 'like', '%' . $query . '%');
            });
        }

        $posts = $posts->orderByDesc('published_at')->paginate(12);

        return view('frontend.search', [
            'posts' => $posts,
            'query' => $query,
        ]);
    }

    // ===== Documentation (photo gallery with categories & tags) =====

    public function documentation()
    {
        $docs = Content::with('type')
            ->whereHas('type', fn($q) => $q->where('slug', 'documentation'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(12);

        $categories = \App\Models\Category::orderBy('name')->get();
        $tags = \App\Models\Tag::orderBy('name')->get();

        return view('frontend.documentation', [
            'docs' => $docs,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function documentationShow($slug)
    {
        $doc = Content::with('type')
            ->whereHas('type', fn($q) => $q->where('slug', 'documentation'))
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Get related docs (same category or tag, excluding current)
        $related = Content::with('type')
            ->whereHas('type', fn($q) => $q->where('slug', 'documentation'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('id', '!=', $doc->id)
            ->limit(4)
            ->get();

        // Get categories and tags for this doc
        $docCategories = \App\Models\Category::whereIn('id', $doc->category_ids ?? [])->get();
        $docTags = \App\Models\Tag::whereIn('id', $doc->tag_ids ?? [])->get();

        return view('frontend.documentation-show', [
            'doc' => $doc,
            'related' => $related,
            'docCategories' => $docCategories,
            'docTags' => $docTags,
        ]);
    }

    public function documentationCategory($slug)
    {
        $category = \App\Models\Category::where('slug', $slug)->firstOrFail();

        $docs = Content::with('type')
            ->whereHas('type', fn($q) => $q->where('slug', 'documentation'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereJsonContains('category_ids', (string) $category->id)
            ->orderByDesc('published_at')
            ->paginate(12);

        $categories = \App\Models\Category::orderBy('name')->get();
        $tags = \App\Models\Tag::orderBy('name')->get();

        return view('frontend.documentation', [
            'docs' => $docs,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => $category,
        ]);
    }

    public function documentationTag($slug)
    {
        $tag = \App\Models\Tag::where('slug', $slug)->firstOrFail();

        $docs = Content::with('type')
            ->whereHas('type', fn($q) => $q->where('slug', 'documentation'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereJsonContains('tag_ids', (string) $tag->id)
            ->orderByDesc('published_at')
            ->paginate(12);

        $categories = \App\Models\Category::orderBy('name')->get();
        $tags = \App\Models\Tag::orderBy('name')->get();

        return view('frontend.documentation', [
            'docs' => $docs,
            'categories' => $categories,
            'tags' => $tags,
            'activeTag' => $tag,
        ]);
    }
}
