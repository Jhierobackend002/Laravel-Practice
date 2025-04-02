<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class MyService
{
    /**
     * @var Sheets Google Sheets service instance.
     */
    private $MyService;

    /**
     * GoogleSheetsApiServices constructor.
     *
     * Initializes the Google Client and sets up authentication for accessing Google Sheets.
     */
    public function __construct()
    {
        // Set up the Google Client
        $client = new Client();
        $client->setAuthConfig(storage_path('app/my-project-test-455503-b978c644bb01dc2fd98.json'));
        $client->addScope(Sheets::SPREADSHEETS);
        $this->services = new Sheets($client);
    }

    private function getSheetId(string $ssid, string $sheet_tab): int
    {
        $spreadsheet = $this->services->spreadsheets->get($ssid);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheet_tab) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        throw new \Exception("Sheet '$sheet_tab' not found.");
    }

    public function sheets(string $spreadsheet_id, string $range)
    {
        return $this->services->spreadsheets_values->get($spreadsheet_id, $range)->getValues();
    }

    public function rows(string $spreadsheet_id, string $range, array $data)
    {
        $body = new Sheets\ValueRange(['values' => $data]);

        $parameters = [
            'valueInputOption' => 'RAW',
            'insertDataOption' => 'INSERT_ROWS',
        ];

        // Append new values
        $appendResponse = $this->services->spreadsheets_values->append($spreadsheet_id, $range, $body, $parameters);

        // Get sheet ID dynamically
        $sheetId = $this->getSheetId($spreadsheet_id, $range);

        // Get total rows (to locate the newly inserted ones)
        $existingData = app(\App\Services\MyService::class)->sheets($spreadsheet_id, $range);
        $startRow = count($existingData) - count($data);
        $endRow = count($existingData);

        $resetFormatRequest = [
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $startRow,
                    'endRowIndex' => $endRow,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 7,
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'textFormat' => [
                            'bold' => false,
                            'fontSize' => 11
                        ],
                        'horizontalAlignment' => 'LEFT'
                    ]
                ],
                'fields' => 'userEnteredFormat(textFormat.bold,textFormat.fontSize,horizontalAlignment)'
            ]
        ];

        // Apply formatting reset
        $this->services->spreadsheets->batchUpdate(
            $spreadsheet_id,
            new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => [$resetFormatRequest]
            ])
        );

        return $appendResponse;
    }

    public function addMergedDate(string $ssid, string $sheet_tab, array $dateEntry)
    {
        $existingData = app(\App\Services\MyService::class)->sheets($ssid, $sheet_tab);
        $sheetId = $this->getSheetId($ssid, $sheet_tab);
        $date_text = $dateEntry[0][0];

        $body = new Sheets\ValueRange(['values' => $dateEntry]);

        $parameters = [
            'valueInputOption' => 'RAW',
            'insertDataOption' => 'INSERT_ROWS',
        ];

        $lastRow = ($existingData !== null) ? count($existingData) : 0;

        $appendResponse = $this->services->spreadsheets_values->append($ssid, $sheet_tab, $body, $parameters);

        $mergeRequest = [
            'mergeCells' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $lastRow,
                    'endRowIndex' => $lastRow + 1,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 7,
                ],
                'mergeType' => 'MERGE_ALL'
            ]
        ];

        $formatRequest = [
            'repeatCell' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'startRowIndex' => $lastRow,
                    'endRowIndex' => $lastRow + 1,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 7,
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'horizontalAlignment' => 'CENTER',
                        'verticalAlignment' => 'MIDDLE',
                        'textFormat' => [
                            'bold' => true,
                            'fontSize' => 11
                        ],
                    ]
                ],
                'fields' => 'userEnteredFormat(horizontalAlignment,verticalAlignment,textFormat.bold,textFormat.fontSize)'
            ]
        ];

        $this->services->spreadsheets->batchUpdate(
            $ssid,
            new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => [$mergeRequest, $formatRequest]
            ])
        );

        return $appendResponse;
    }
}
