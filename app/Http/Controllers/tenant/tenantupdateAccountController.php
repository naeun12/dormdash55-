<?php

namespace App\Http\Controllers\tenant;

use App\Http\Controllers\Controller;
use App\Models\tenant\tenantModel;
use Illuminate\Http\Request;

class tenantupdateAccountController extends Controller
{
    // Proseso sa pag-update sa tenant account information.
    public function updateAccount(Request $request, $id)
    {
        // Pangitaon ang tenant record base sa iyang ID.
        $tenant = tenantModel::find($id);

        // Ibalik ang 404 response kung walay tenant nga nakit-an.
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        // I-update ang tenant information gamit ang data nga nadawat gikan sa request.
        $tenant->update($request->all());

        // Ibalik ang success response human ma-update ang account.
        return response()->json(['message' => 'Account updated successfully']);
    }
}