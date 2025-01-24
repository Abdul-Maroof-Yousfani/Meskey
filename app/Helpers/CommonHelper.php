<?php

namespace App\Helpers;

use App\Models\Airport;
use App\Models\Basket;
use App\Models\BasketItem;
use App\Models\Lead;
use App\Models\LeadAssigneeBridge;
use App\Models\LeadStages;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Leads;
use App\Models\LeadStageBridge;
use Illuminate\Http\UploadedFile;
use App\Models\Media;
use DB;


class CommonHelper
{
    public static function autoLeadNumber()
    {
        $latestLead = Leads::select('inquiry_no')->orderBy('inquiry_no', 'desc')->first();

        $nextLeadNumber = $latestLead ? $latestLead->inquiry_no + 1 : 1;

        return $formattedLeadNumber = str_pad($nextLeadNumber, 6, '0', STR_PAD_LEFT);
    }
    public static function getProductName($slug)
    {
        $product = Airport::where('slug', $slug)->select('title')->first();
        return $product->title;
    }
    public static function formatTime($time)
    {
        // Assuming $time is in 'Y-m-d H:i:s' format
        return date('d-M-Y', strtotime($time)); // Returns time in 'hh:mm AM/PM' format
    }
    public static function leadStageBridgeInsert($lead_id, $stage_id)
    {
        $leadbridge = LeadStageBridge::where('lead_id', $lead_id)->get()->last();
        if ($leadbridge) {
            if ($leadbridge->stage_id != $stage_id) {
                LeadStageBridge::create(['lead_id' => $lead_id, 'stage_id' => $stage_id]);
                return true;
            }
        } else {
            LeadStageBridge::create(['lead_id' => $lead_id, 'stage_id' => $stage_id]);
        }
    }

    public static function leadAssignBridgeInsert($lead_id, $assignee, $watcher = null)
    {
        $leadbridge = LeadAssigneeBridge::where('lead_id', $lead_id)->get()->last();
        if ($leadbridge) {
            if ($leadbridge->assign_to != $assignee || $leadbridge->watcher != $watcher) {
                LeadAssigneeBridge::create(['lead_id' => $lead_id, 'assign_to' => $assignee, 'watcher' => $watcher]);
                return true;
            }
        } else {
            LeadAssigneeBridge::create(['lead_id' => $lead_id, 'assign_to' => $assignee, 'watcher' => $watcher]);
        }
    }


    public static function uploadMedia(UploadedFile $file)
    {
        $path = $file->store('/');

        $media = Media::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'type' => $file->getClientOriginalExtension(),
            // Add more fields as needed
        ]);

        return $media;
    }

    public static function getGenericItemsByCollection($collection, $withoutCurrency = 0)
    {
        $totalPrice = 0;
        $totalPriceHigh = 0;
        $totalImages = 0;
        $totalVideos = 0;

        foreach ($collection->media as $key => $value) {
            $standardImgPrice = (isset($value->event->price_per_image)
                ? (int) $value->event->price_per_image
                : 0);

            $standardVidPrice = (isset($value->event->price_per_video)
                ? (int) $value->event->price_per_video
                : 0);

            $highImgPrice = (isset($value->event->price_per_high_image)
                ? (int) $value->event->price_per_high_image
                : 0);

            $highVidPrice = (isset($value->event->price_per_high_video)
                ? (int) $value->event->price_per_high_video
                : 0);

            $totalPrice += $value->file_type === 'image' ? $standardImgPrice : $standardVidPrice;
            $totalPriceHigh += $value->file_type === 'image' ? $highImgPrice : $highVidPrice;

            $totalImages += $value->file_type === 'image' ? 1 : 0;
            $totalVideos += $value->file_type === 'video' ? 1 : 0;
        }

        return ['total_media' => count($collection->media), 'price' => $withoutCurrency ? number_format($totalPrice, 2) : ("$" . number_format($totalPrice, 2)), 'high_price' => $withoutCurrency ?  number_format($totalPriceHigh, 2) : ("$" . number_format($totalPriceHigh, 2)), 'img_count' => $totalImages, 'vid_count' => $totalVideos];
    }

    public static function getMediaPrices($media)
    {
        if (!$media || !$media->event) {
            return null;
        }

        $prices = [
            'price_per_image' => $media->event->price_per_image ?? 0,
            'price_per_video' => $media->event->price_per_video ?? 0,
            'price_per_high_image' => $media->event->price_per_high_image ?? 0,
            'price_per_high_video' => $media->event->price_per_high_video ?? 0,
        ];

        return $prices;
    }

    public static function getCartCount($basketId)
    {
        // Retrieve count of items in the basket
        return BasketItem::where('basket_id', $basketId)->count();
    }

    public static function getBasketItemIds()
    {
        // Get the current authenticated user
        $user = auth()->user();
        $itemsByType = [
            'media' => [],
            'collection' => [],
            'event' => []
        ];

        if (!$user) {
            return $itemsByType;
        }

        // Find the user's active basket
        $basket = Basket::where('user_id', $user->id)
            ->where('status', 1) // Assuming '1' means active
            ->first();

        if (!$basket) {
            return $itemsByType;
        }

        // Initialize an array to store item IDs grouped by item_type
        $itemsByType = [
            'media' => [],
            'collection' => [],
            'event' => []
        ];

        // Retrieve all items in the basket, grouped by item_type
        $basketItems = BasketItem::where('basket_id', $basket->id)
            ->get(['item_id', 'item_type']); // Get 'id' and 'item_type' columns

        // Loop through the items and categorize them by item_type
        foreach ($basketItems as $item) {
            if (isset($itemsByType[$item->item_type])) {
                $itemsByType[$item->item_type][] = $item->item_id;
            }
        }

        $itemsByType['media'] = array_unique($itemsByType['media']);
        $itemsByType['collection'] = array_unique($itemsByType['collection']);
        $itemsByType['event'] = array_unique($itemsByType['event']);

        return $itemsByType;
    }

    public static function leadProgressPercent($id)
    {
        $l = Leads::whereId($id)->with('stages')->first();
        $current = $l->stages->groupBy('id')->count();
        $stage = LeadStages::whereStatus(1)->get()->count();
        return $current / $stage * 100;
    }


    public static function getFirstColumn($table, $column, $id, $deletedtext = true)
    {

        $q = DB::table($table)
            ->where('id', $id)
            ->select($column, 'deleted_at')
            ->first();
        if ($q) {
            if ($deletedtext) {
                return $q->deleted_at == null ? $q->$column : $q->$column . ' ' . '<span class="deleted-error text-danger text-uppercase"><small>(Discontinued/Deleted)</small></span>';
            } else {
                return $q->deleted_at == null ? $q->$column : $q->$column;
            }
        }
    }
}
