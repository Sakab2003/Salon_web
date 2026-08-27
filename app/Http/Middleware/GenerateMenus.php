<?php

namespace App\Http\Middleware;

use App\Trait\Menu;
use Illuminate\Support\Arr;
use Modules\MenuBuilder\Models\MenuBuilder;

class GenerateMenus
{
    use Menu;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($menuname, $type, $arraymenu)
    {
        \Menu::make($menuname, function ($menu) use ($type, $arraymenu) {
            MenuBuilder::flushCache();
            $menuArray = MenuBuilder::getAllMenu()->where('menu_type', $type);

            $needsRebuild = (count($menuArray) == 0) 
                || MenuBuilder::where('title', 'sidebar.models')->exists()
                || MenuBuilder::where('title', 'sidebar.orders')->exists()
                || MenuBuilder::where('title', 'sidebar.categories')->exists()
                || !MenuBuilder::where('title', 'sidebar.commissions')->exists()
                || !MenuBuilder::where('title', 'sidebar.hairstyle_models')->whereNull('parent_id')->exists()
                || !MenuBuilder::where('title', 'sidebar.pos_sale')->exists()
                || !MenuBuilder::where('title', 'sidebar.financial_balance')->exists();

            if ($needsRebuild) {
                MenuBuilder::where('menu_type', $type)->delete();
                MenuBuilder::flushCache();
                $arr = [];
                foreach (config('menubuilder.'.$arraymenu) as $key => $value) {
                    $arr[] = array_merge(config('menubuilder.MENU'), $value);
                }
                foreach ($arr as $key => $value) {
                    $this->saveMenu($value);
                }
                MenuBuilder::flushCache();
                $menuArray = MenuBuilder::getAllMenu()->where('menu_type', $type);
            }

            $hasReviewInDb = false;
            foreach ($menuArray as $key => $value) {
                if ($value->route == 'backend.employees.review' || (isset($value->url) && (str_contains($value->url, 'avis-clients') || str_contains($value->url, 'employees-review')))) {
                    $hasReviewInDb = true;
                    $value->status = 1;
                    $value->active = ['app/employees-review*', 'app/avis-clients*'];
                    $value->permission = ['view_customer_reviews'];
                }
                if ($value->status) {
                    $this->makeMenu($menu, $value);
                }
            }

            if (!$hasReviewInDb) {
                $this->makeMenu($menu, new MenuBuilder([
                    'menu_type' => $type,
                    'menu_item_type' => 'link',
                    'title' => 'sidebar.reviews',
                    'short_title' => '-',
                    'is_route' => true,
                    'route' => 'backend.employees.review',
                    'url' => 'app/avis-clients',
                    'active' => ['app/employees-review*', 'app/avis-clients*'],
                    'order' => 10,
                    'menu_level' => 0,
                    'start_icon' => 'fa-solid fa-star',
                    'permission' => ['view_customer_reviews'],
                    'status' => 1,
                ]));
            }

            // Access Permission Check
            $menu->filter(function ($item) {
                if (auth()->check() && auth()->user()->hasRole('manager')) {
                    $isBranchMenu = ($item->title == __('sidebar.branches'))
                        || (isset($item->nickname) && $item->nickname == 'branch')
                        || (isset($item->activematches) && in_array('app/branch', (array)$item->activematches))
                        || ($item->url() && str_contains($item->url(), 'branch'));
                    if ($isBranchMenu) {
                        return false;
                    }
                }
                if ($item->data('permission')) {
                    if (auth()->check()) {
                        if (auth()->user()->hasRole('admin')) {
                            return true;
                        }
                        if (auth()->user()->hasRole('manager')) {
                            $isReview = ($item->title == __('sidebar.reviews'))
                                || (isset($item->nickname) && $item->nickname == 'review')
                                || ($item->url() && (str_contains($item->url(), 'avis-clients') || str_contains($item->url(), 'employees-review')));
                            if ($isReview) {
                                return true;
                            }
                            $isModelSection = ($item->title == __('sidebar.hairstyle_models'))
                                || (isset($item->nickname) && $item->nickname == 'hairstyle_models')
                                || ($item->url() && str_contains($item->url(), 'hairstyle-models'));
                            if ($isModelSection) {
                                return true;
                            }
                            $isShopOrFinance = ($item->title == __('sidebar.pos_sale'))
                                || ($item->title == __('sidebar.products_stock'))
                                || ($item->title == __('sidebar.sales_history'))
                                || ($item->title == __('sidebar.financial_balance'))
                                || ($item->title == __('sidebar.shop'))
                                || ($item->url() && (str_contains($item->url(), 'ventes') || str_contains($item->url(), 'products') || str_contains($item->url(), 'orders') || str_contains($item->url(), 'bilan-financier')));
                            if ($isShopOrFinance) {
                                return true;
                            }
                        }
                        if (auth()->user()->hasAnyPermission($item->data('permission'))) {
                            return true;
                        }
                    }

                    return false;
                } else {
                    return true;
                }
            });
            // Set Active Menu
            $menu->filter(function ($item) {
                $isActive = false;
                if ($item->activematches) {
                    $activematches = (is_string($item->activematches)) ? [$item->activematches] : $item->activematches;
                    foreach ($activematches as $pattern) {
                        $p = trim($pattern, '/');
                        if (request()->is($p) || request()->is($p.'*') || request()->is('*'.$p.'*')) {
                            $isActive = true;
                            break;
                        }
                    }
                }

                if (!$isActive) {
                    if (request()->routeIs('backend.employees.review') || request()->is('app/avis-clients*') || request()->is('app/employees-review*')) {
                        if ($item->title == __('sidebar.reviews') || str_contains($item->url() ?? '', 'avis-clients') || str_contains($item->url() ?? '', 'employees-review')) {
                            $isActive = true;
                        }
                    }
                }

                if ($isActive) {
                    $item->active();
                    $item->link->active();
                    if ($item->hasParent() && $item->parent()) {
                        $item->parent()->active();
                    }
                }

                return true;
            });
        })->sortBy('order');

        return \Menu::get($menuname);
    }

    protected function saveMenu($menu)
    {
        $menuChildren = $menu['children'] ?? null;
        $menu = Arr::except($menu, ['children']);
        $savedMenu = MenuBuilder::create($menu);
        if (isset($menuChildren) && count($menuChildren) > 0) {
            foreach ($menuChildren as $key => $value) {
                $value['parent_id'] = $savedMenu->id;
                $this->saveMenu($value);
            }
        }
    }

    protected function makeMenu($menu, $value)
    {
        if ($value->menu_item_type == 'static') {
            $this->staticMenu($menu, ['title' => __($value->title), 'order' => $value->order, 'permission' => $value->permission]);
        } else {
            if (count($value->children) > 0) {
                $parentMenuArr = [
                    'icon' => $value->start_icon,
                    'title' => __($value->title),
                    'active' => $value->active,
                    'nickname' => $value->nickname ?? \Str::slug($value->title),
                    'order' => $value->order,
                    'permission' => $value->permission,
                ];
                if (isset($value->parent)) {
                    $parentMenuArr['parent'] = $value->parent->nickname;
                }
                $parentMenu = $this->parentMenu($menu, $parentMenuArr);
                foreach ($value->children as $key => $childValue) {
                    $childArr = [
                        'title' => __($childValue->title),
                        'active' => $childValue->active,
                        'order' => $childValue->order,
                    ];

                    if (isset($childValue->start_icon)) {
                        $childArr['icon'] = $childValue->start_icon;
                    }

                    if ($childValue->is_route) {
                        if (isset($childValue->route)) {
                            $childArr['route'] = $childValue->route;
                        }
                    } else {
                        if (isset($childValue->url)) {
                            $childArr['url'] = $childValue->url;
                        }
                    }
                    if (isset($childValue['permission']) && count($childValue['permission']) > 0) {
                        $childArr['permission'] = $childValue->permission;
                    }
                    if (isset($childValue['target_type'])) {
                        $childArr['target'] = $childValue->target_type;
                    }
                    if (isset($childValue->children) && count($childValue->children) > 0) {
                        $this->makeMenu($parentMenu, $childValue);
                    } else {
                        switch ($childValue->menu_item_type) {
                            case 'static':
                                $this->staticMenu($parentMenu, ['title' => __($childValue->title), 'order' => $childValue->order, 'permission' => $childValue->permission]);
                                break;

                            case 'parent':
                                $this->makeMenu($parentMenu, $childValue);
                                break;
                            default:
                                $this->childMain($parentMenu, $childArr);
                                break;
                        }
                    }
                }
            } else {
                $arr = [
                    'icon' => $value->start_icon,
                    'title' => __($value->title),
                    'active' => $value->active,
                    'order' => $value->order,
                ];
                if ($value->is_route) {
                    if (isset($value->route)) {
                        $arr['route'] = $value->route;
                    }
                } else {
                    if (isset($value->url)) {
                        $arr['url'] = $value->url;
                    }
                }
                if (isset($value['permission']) && count($value['permission']) > 0) {
                    $arr['permission'] = $value->permission;
                }
                if (isset($value['target_type'])) {
                    $arr['target'] = $value->target_type;
                }
                $this->mainRoute($menu, $arr);
            }
        }
    }
}

