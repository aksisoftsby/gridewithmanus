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

    public function proposal()
    {
        return view('public.proposal');
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

    public function iklanIndex(Request $request)
    {
        $search = $request->query('search');
        $cat = $request->query('category_id');

        if (!DB::getSchemaBuilder()->hasTable('iklan_gratis')) {
            return view('public.iklan', ['iklan' => collect(), 'categories' => collect(), 'search' => $search, 'cat' => $cat]);
        }

        $query = DB::table('iklan_gratis')
            ->leftJoin('iklan_gratis_categories', 'iklan_gratis.category_id', '=', 'iklan_gratis_categories.id')
            ->select('iklan_gratis.*', 'iklan_gratis_categories.name as category_name');

        $query->where('iklan_gratis.status', 'ACTIVE');
        $query->where(function ($q) {
            $q->where('iklan_gratis.expired_at', '>=', now())->orWhereNull('iklan_gratis.expired_at');
        });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('iklan_gratis.title', 'like', '%' . $search . '%')
                  ->orWhere('iklan_gratis.description', 'like', '%' . $search . '%')
                  ->orWhere('iklan_gratis.city', 'like', '%' . $search . '%');
            });
        }

        if ($cat) {
            $query->where('iklan_gratis.category_id', $cat);
        }

        $iklan = $query->orderBy('iklan_gratis.created_at', 'desc')->paginate(12)->withQueryString();
        $categories = DB::table('iklan_gratis_categories')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('public.iklan', compact('iklan', 'categories', 'search', 'cat'));
    }

    public function iklanDetail($id)
    {
        if (!DB::getSchemaBuilder()->hasTable('iklan_gratis')) {
            abort(404);
        }

        $item = DB::table('iklan_gratis')
            ->leftJoin('iklan_gratis_categories', 'iklan_gratis.category_id', '=', 'iklan_gratis_categories.id')
            ->select('iklan_gratis.*', 'iklan_gratis_categories.name as category_name')
            ->where('iklan_gratis.id', $id)
            ->where('iklan_gratis.status', 'ACTIVE')
            ->where(function ($q) {
                $q->where('iklan_gratis.expired_at', '>=', now())->orWhereNull('iklan_gratis.expired_at');
            })
            ->first();

        if (!$item) {
            abort(404);
        }

        $related = DB::table('iklan_gratis')
            ->where('iklan_gratis.status', 'ACTIVE')
            ->where('iklan_gratis.id', '!=', $item->id)
            ->where(function ($q) {
                $q->where('iklan_gratis.expired_at', '>=', now())->orWhereNull('iklan_gratis.expired_at');
            })
            ->when($item->category_id, fn ($q) => $q->where('category_id', $item->category_id))
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        return view('public.iklan-detail', compact('item', 'related'));
    }
}
