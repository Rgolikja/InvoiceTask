<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Import;
use App\Imports\InvoicesImport;
use App\Services\Interfaces\ImportServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService implements ImportServiceInterface
{
    // Import Excel file
    public function importExcelData(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName(); // keep original name
        $path = $file->storeAs('imports', $originalName, 'public');

        $import = Import::create([
            'file_name' => $originalName,
            'file_path' => $path,
            'status' => 'processed',
        ]);

        try {
            // Get total rows
            $collection = Excel::toCollection(new InvoicesImport, storage_path('app/public/' . $path));
            $totalRows = $collection->first()->count();

            Excel::import(new InvoicesImport, storage_path('app/public/' . $path));

            // Update import record
            $import->update([
                'status' => 'completed',
                'rows_total' => $totalRows,
                'rows_imported' => $totalRows,
                'error_message' => null,
            ]);

        } catch (\Exception $e) {
            // Handle errors
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $import;
    }

    public function updateImport(int $id, Request $request)
    {
        $import = Import::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|string|in:processed,completed,failed',
        ]);
        $import->update($validated);
        return $import;
    }

    public function deleteImport(int $id)
    {
        $import = Import::findOrFail($id);

        if (Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }

        $import->delete();
        return true;
    }

    public function deleteAllImports()
    {
        $imports = Import::all();

        foreach ($imports as $import) {
            if (Storage::disk('public')->exists($import->file_path)) {
                Storage::disk('public')->delete($import->file_path);
            }
            $import->delete();
        }

        return true;
    }

    public function getAllImports(int $perPage = 10)
    {
        return Import::paginate($perPage);
    }
    public function getImportById(int $id)
    {
        return Import::findOrFail($id);
    }

}
