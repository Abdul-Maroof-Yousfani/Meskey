<?php

namespace App\Observers;

use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArrivalTicketObserver
{
    /**
     * Handle the ArrivalTicket "creating" event.
     */
    public function creating(ArrivalTicket $arrivalTicket)
    {
        $authUser = auth()->user();
        $companyLocation = $authUser->companyLocation ?? null;
        $code = $companyLocation->code ?? 'KHI';

        $arrivalTicket->unique_no = generateTicketNoWithDateFormat('arrival_tickets', $code);
    }
    /**
     * Handle the ArrivalTicket "created" event.
     */
    public function created(ArrivalTicket $arrivalTicket): void
    {
        ArrivalSamplingRequest::create([
            'company_id'       => $arrivalTicket->company_id,
            'arrival_ticket_id' => $arrivalTicket->id,
            'sampling_type'    => 'initial',
            'is_re_sampling'   => 'no',
            'is_done'          => 'no',
            'remark'           => null,
        ]);
    }

    /**
     * Handle the ArrivalTicket "updating" event.
     */
    public function updating(ArrivalTicket $ticket)
    {
        $this->handleBiltyReturnAttachment($ticket);
    }

    /**
     * Handle bilty return attachment upload
     */
    protected function handleBiltyReturnAttachment(ArrivalTicket $ticket)
    {
        if (request()->hasFile('bilty_return_attachment')) {
            if ($ticket->bilty_return_attachment) {
                $this->deleteFile($ticket->bilty_return_attachment);
            }

            $file = request()->file('bilty_return_attachment');
            $path = $file->store('bilty_return_attachments', 'public');

            $ticket->bilty_return_attachment = 'storage/' . $path;
        }
    }

    /**
     * Delete file from storage
     */
    protected function deleteFile($filePath)
    {
        // $path = str_replace('storage/', '', $filePath);
        // if (Storage::disk('public')->exists($path)) {
        //     Storage::disk('public')->delete($path);
        // }

        $path = public_path('storage/' . str_replace('storage/', '', $filePath));
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Handle the ArrivalTicket "deleted" event.
     */
    public function deleted(ArrivalTicket $ticket)
    {
        if ($ticket->bilty_return_attachment) {
            $this->deleteFile($ticket->bilty_return_attachment);
        }
    }
}
