<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;

/** Matrice des habilitations en lecture seule (la modification passe par un seeder versionné). */
class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions:id,slug')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('module')->orderBy('slug')->get()->groupBy('module'),
        ]);
    }
}
