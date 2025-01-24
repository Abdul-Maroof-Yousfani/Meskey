<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Leads;

class LeadActivityHelper
{
    public static function getActivity($lead_id)
    {
        $leadInteractions = Leads::find($lead_id)
            ->interactions()
            ->orderBy('created_at', 'desc') // Ensure the interactions are sorted by the latest first
            ->get()
            ->groupBy(function ($interaction) {
                return $interaction->created_at->format('Y-m-d'); // Group interactions by date
            });
      return  $leadInteractions;
    }

    public static function leadInteractionInsert($table ,$request ,$type ,$content,$icon)
    {

             $table->interactions()->create([
            'interactable_id' => $request->lead_id,
            'lead_id' => $request->lead_id,
            'content' => $content,
            'user_id' => auth()->user()->id,
            'activity_type' => $type,
            'material_icon' => $icon,
        ]);
    }
}
