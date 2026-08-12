<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type'); // FOOD, MART, SHOP
        $search = $request->query('search');

        $query = DB::table('merchants')->where('status', 'ACTIVE');

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $merchants = $query->orderBy('rating', 'desc')->paginate(12);
        $promos = DB::table('promos')->where('is_active', true)->limit(6)->get();

        $news = collect();
        $testimonials = collect();
        if (DB::getSchemaBuilder()->hasTable('news')) {
            $news = DB::table('news')
                ->leftJoin('news_categories', 'news.news_category_id', '=', 'news_categories.id')
                ->select('news.*', 'news_categories.name as category_name')
                ->where('status', 'PUBLISHED')
                ->whereNotNull('published_at')
                ->orderBy('published_at', 'desc')
                ->limit(6)
                ->get();
        }
        if (DB::getSchemaBuilder()->hasTable('testimonials')) {
            $testimonials = DB::table('testimonials')
                ->where('is_published', true)
                ->orderBy('rating', 'desc')
                ->limit(6)
                ->get();
        }

        return view('public.index', compact('merchants', 'promos', 'type', 'search', 'news', 'testimonials'));
    }

    public function merchantDetail($slug)
    {
        $merchant = DB::table('merchants')->where('slug', $slug)->first();
        if (!$merchant) {
            abort(404);
        }

        $menuItems = DB::table('menu_items')->where('merchant_id', $merchant->id)->get();

        return view('public.merchant', compact('merchant', 'menuItems'));
    }

    public function newsDetail($slug)
    {
        if (!DB::getSchemaBuilder()->hasTable('news')) {
            abort(404);
        }

        $item = DB::table('news')
            ->leftJoin('news_categories', 'news.news_category_id', '=', 'news_categories.id')
            ->select('news.*', 'news_categories.name as category_name')
            ->where('news.slug', $slug)
            ->where('news.status', 'PUBLISHED')
            ->first();
        if (!$item) {
            abort(404);
        }

        return view('public.news-detail', compact('item'));
    }
}
