<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Import;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Services\ElifApiService;
use PhpOffice\PhpSpreadsheet\IOFactory;


class InvoiceProcessController extends Controller
{
    protected ElifApiService $elifService;

    public function __construct(ElifApiService $elifService)
    {
        $this->elifService = $elifService;
    }


    public function process()
    {
        DB::beginTransaction();

        try {
            //marrim cdo import te cilat jan the pa procesuara
            $imports = Import::where('processed', false)->get();
            //kontrollojme nese ka importe per tu procesuar nese jo japim error message
            if ($imports->isEmpty()) {
                return response()->json([
                    'message' => 'No imports to process',

                ], 200);

            }
            //bejme loop tek importet
            foreach ($imports as $import) {
                $invoiceData = [
                    'invoice_number' => $import->invoice_numnber,
                    'invoice_date' => $import->invoice_date,
                    'total_amount_eur' => $import->total_amount_eur,
                    'total_with_vat' => $import->total_with_vat,
                    'total_amount_all' => $import->total_amount_all,
                    'currency' => $import->currency,
                    'base_currency' => $import->base_currency,
                ];
                //krijojm nje invoice me te dhenat e ruajtura
                $invoice = Invoice::create($invoiceData);
                //loop dhe te items
                $items = $import->items;
                foreach ($items as $item) {
                    $itemData = [
                        'invoice_id' => $invoice->id,
                        'product_name' => $item['product_name'],
                        'unit' => $item['unit'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'vat_amount' => $item['vat_amount'],
                    ];
                    //ruajm cdo invoice item
                    InvoiceItem::create($itemData);
                }
                //e ruajm import si te procesuar
                $import->processed = true;
                $import->save();
            }
            //bejm commit transaksionin
            DB::commit();
            return response()->json([
                'message' => 'Import succesful'
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'message' => 'Error processing imports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}