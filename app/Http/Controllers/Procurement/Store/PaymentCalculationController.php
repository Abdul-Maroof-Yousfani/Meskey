<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;


class PaymentCalculationController extends Controller
{
    /**
     * Calculate payment details for a ticket with sauda type in URL
     * 
     * @param int $ticketId
     * @param int $saudaType
     * @return JsonResponse
     */
    public function calculateTicketPaymentWithSauda($ticketId, $saudaType): JsonResponse
    {
        try {
            // Validate parameters
            $this->validateParameters($ticketId, $saudaType);

            // Calculate payment details
            $paymentDetails = calculatePaymentDetails($ticketId, $saudaType);

            return $this->successResponse($paymentDetails, 'Payment details calculated successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Calculate payment details for a ticket (sauda type auto-detected or passed as query param)
     * 
     * @param int $ticketId
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateTicketPayment($ticketId, Request $request): JsonResponse
    {
        try {
            $saudaType = $request->query('sauda_type');

            // If sauda_type not provided, try to detect it
            if (!$saudaType) {
                $saudaType = $this->detectSaudaType($ticketId);
            }

            // Validate parameters
            $this->validateParameters($ticketId, $saudaType);

            // Calculate payment details
            $paymentDetails = calculatePaymentDetails($ticketId, $saudaType);

            return $this->successResponse($paymentDetails, 'Payment details calculated successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Calculate payment details using query parameters
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculatePayment(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'ticket_id' => 'required|integer|min:1',
                'sauda_type' => 'required|integer|in:1,2'
            ]);

            $ticketId = $validated['ticket_id'];
            $saudaType = $validated['sauda_type'];

            // Calculate payment details
            $paymentDetails = calculatePaymentDetails($ticketId, $saudaType);

            return $this->successResponse($paymentDetails, 'Payment details calculated successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get multiple ticket calculations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateMultiplePayments(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'tickets' => 'required|array|min:1|max:50',
                'tickets.*.ticket_id' => 'required|integer|min:1',
                'tickets.*.sauda_type' => 'required|integer|in:1,2'
            ]);

            $results = [];
            $errors = [];

            foreach ($validated['tickets'] as $index => $ticket) {
                try {
                    $paymentDetails = calculatePaymentDetails(
                        $ticket['ticket_id'],
                        $ticket['sauda_type']
                    );

                    $results[] = [
                        'ticket_id' => $ticket['ticket_id'],
                        'sauda_type' => $ticket['sauda_type'],
                        'data' => $paymentDetails,
                        'status' => 'success'
                    ];
                } catch (Exception $e) {
                    $errors[] = [
                        'ticket_id' => $ticket['ticket_id'],
                        'sauda_type' => $ticket['sauda_type'],
                        'error' => $e->getMessage(),
                        'status' => 'error'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk calculation completed',
                'data' => [
                    'successful' => $results,
                    'failed' => $errors,
                    'summary' => [
                        'total_requested' => count($validated['tickets']),
                        'successful_count' => count($results),
                        'failed_count' => count($errors)
                    ]
                ]
            ], 200);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get payment summary for a ticket
     * 
     * @param int $ticketId
     * @param Request $request
     * @return JsonResponse
     */
    public function getPaymentSummary($ticketId, Request $request): JsonResponse
    {
        try {
            $saudaType = $request->query('sauda_type');

            if (!$saudaType) {
                $saudaType = $this->detectSaudaType($ticketId);
            }

            $this->validateParameters($ticketId, $saudaType);

            $paymentDetails = calculatePaymentDetails($ticketId, $saudaType);

            // Return only summary data
            $summary = [
                'ticket_id' => $ticketId,
                'ticket_type' => $paymentDetails->ticket_type,
                'basic_info' => $paymentDetails->basic_info,
                'calculations' => $paymentDetails->calculations,
                'payment_history' => $paymentDetails->payment_history,
            ];

            if ($paymentDetails->ticket_type === 'thadda') {
                $summary['freight_info'] = $paymentDetails->freight_info;
            }

            return $this->successResponse($summary, 'Payment summary retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Validate parameters
     * 
     * @param int $ticketId
     * @param int $saudaType
     * @throws ValidationException
     */
    private function validateParameters($ticketId, $saudaType): void
    {
        if (!is_numeric($ticketId) || $ticketId < 1) {
            throw ValidationException::withMessages([
                'ticket_id' => ['Ticket ID must be a positive integer']
            ]);
        }

        if (!in_array($saudaType, [1, 2])) {
            throw ValidationException::withMessages([
                'sauda_type' => ['Sauda type must be 1 (Pohanch) or 2 (Thadda)']
            ]);
        }
    }

    /**
     * Try to detect sauda type from ticket
     * 
     * @param int $ticketId
     * @return int
     * @throws Exception
     */
    private function detectSaudaType($ticketId): int
    {
        // Try to find in ArrivalTicket first
        $arrivalTicket = \App\Models\Arrival\ArrivalTicket::find($ticketId);
        if ($arrivalTicket) {
            return $arrivalTicket->sauda_type_id ?? 1; // Default to Pohanch
        }

        // Try to find in PurchaseTicket
        $purchaseTicket = \App\Models\PurchaseTicket::find($ticketId);
        if ($purchaseTicket) {
            return 2; // Thadda
        }

        throw new Exception("Ticket with ID {$ticketId} not found in either ArrivalTicket or PurchaseTicket");
    }

    /**
     * Return success response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    private function successResponse($data, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $statusCode);
    }

    /**
     * Return error response
     * 
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    private function errorResponse(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
