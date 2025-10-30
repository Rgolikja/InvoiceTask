<?php

namespace App\Services\Interfaces;

use Illuminate\Http\Request;

interface ImportServiceInterface
{
    /**
     * import excel
     */
    public function importExcelData(Request $request);

    /**
     * update nje importi aktual
     */
    public function updateImport(int $id, Request $request);

    /**
     * fshir nje import
     */
    public function deleteImport(int $id);

    /**
     * get cdo import
     */
    public function getAllImports(int $perPage);
}
