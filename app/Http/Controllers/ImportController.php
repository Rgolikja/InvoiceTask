<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Import;

class ImportController extends Controller
{
    public function index()
    {
        $imports = Import::all();
        return response()->json($imports);
    }

    public function store(Request $request)
    {
        // 1. Validate the uploaded file

        $request->validate([
            'file' => 'required|mimes:jpg,png,pdf|max:2048'
        ]);
        // 2. Check if a file was actually uploaded

        if ($request->hasFile('file')) {
            // 3. Get the uploaded file

            $file = $request->file('file');
            // 4. Store the file on a specified disk public/imports
            $path = $file->store('imports', 'public');
            // save the file info
            $import = Import::create([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'uploaded'
            ]);
            return response()->json([
                'message' => 'File Uploaded Succesfully',
                'import' => $import
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        //Fin the import by id or throw exeption
        $import = Import::findOrFail($id);
        //validate data
        $validated = $request->validate([
            'status' => $request->status ?? $import->status,
        ]);
        return response()->json([
            'message' => 'Import Succesful',
            'import' => $import,
        ]);
    }

    public function destroy($id)
    {
        $import = Import::findOrFail($id);

        if (Storage::disk('public')->exists($import->file_path)) {
            Storage::disk('public')->delete($import->file_path);
        }
        $import->delete();

        return response()->json([
            'message' => 'Import deleted successfully'
        ]);
    }
}
