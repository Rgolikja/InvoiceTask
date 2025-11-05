<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImportService;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        return response()->json($this->importService->getAllImports(10));
    }

    public function importExcelData(Request $request)
    {
        $result = $this->importService->importExcelData($request);
        return response()->json($result);
    }

    public function update(Request $request, $id)
    {
        $result = $this->importService->updateImport($id, $request);
        return response()->json([
            'message' => 'Import updated successfully',
            'import' => $result,
        ]);
    }

    public function destroy($id)
    {
        $this->importService->deleteImport($id);
        return response()->json([
            'message' => 'Import deleted successfully',
        ]);
    }

    public function show($id)
    {
        $import = $this->importService->getImportById($id);
        return response()->json($import);
    }
}
