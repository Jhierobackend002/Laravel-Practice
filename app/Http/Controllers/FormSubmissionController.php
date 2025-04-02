<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleSheetsApiRequest;
use App\Services\GoogleServices;
use Illuminate\Http\JsonResponse;

/**
 * Class GoogleSheetApiController
 * Handles operations related to writing data into Google Sheets.
 * 
 * @package App\Http\Controllers
 */
class FormSubmissionController extends Controller
{
    /**
     * @var GoogleApiServices
     * Service to interact with Google Sheets API.
     */
    private $api_services;

    /**
     * GoogleSheetApiController constructor.
     *
     * @param GoogleApiServices $services to handle Google Sheets API operations.
     */
    public function __construct(GoogleServices $api_services)
    {
        $this->api_services = $api_services;
    }

    /**
     * Writes form data to a Google Sheet.
     *
     * This method validates the input data and writes it to the specified Google Sheet.
     * It also checks if the header row exists before adding it.
     *
     * @param GoogleSheetApiRequest $form_request The validated form request containing data.
     * 
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function writeSheet(GoogleSheetsApiRequest $request): JsonResponse
    {
        try {
            // Google Sheets settings
            $ssid = env('SheetId');
            $sheet_tab = env('Sheets'); // Sheet2!A1 | Sheet2

            // Define header columns
            $column_header = ['Full Name', 'Email', 'Phone', 'Inquiry Type', 'Country', 'Accept Privacy', 'Date'];

            empty($this->api_services->sheets($ssid, $sheet_tab)) ? $this->api_services->rows($ssid, $sheet_tab, [$column_header]) : false;

            // Prepare the data to be written
            $input = [
                $request->input('fullname'),
                $request->input('email'),
                $request->input('phone'),
                $request->input('inquiry_type'),
                $request->input('country') === 'Africa'  ? $request->input('country') : 'Africa'  ,
                $request->boolean('accept_privacy') ? "Accepted" : false,
                $request->input('date_now') === Null ? date('D M d, Y, h:i:s') : $request->input('date_now')
            ];

            //Google Sheet add API
            $date_text = date('D M d'); // Example: "Thu Mar 23"
            $existingData = app(\App\Services\GoogleServices::class)->sheets($ssid, $sheet_tab);
            $key = false;

            foreach ($existingData as $index => $row) {
                if ($row[0] === $date_text) {
                    $key = $index;
                    break;
                }
            }
            
            if ($key === false) {
                $this->api_services->addMergedRow($ssid, $sheet_tab, [[$date_text]]);
                $this->api_services->addMergedDate($ssid,$sheet_tab,[[$date_text]]);
            }
            
            $this->api_services->rows($ssid, $sheet_tab, [$input]);

            $this->api_services->addRow($ssid, 6, $sheet_tab);

            // Return success response
            return response()->json(['response' => 'Successfully Saved'], 201);

        } catch (\Exception $e) {
            // Return error response if something goes wrong
            return response()->json([
                'error' => 'An error occurred while saving data',
                'message' => $e->getMessage()
            ]);
        }
    }
}
