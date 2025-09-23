<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        // 月指定（YYYY-MM）。未指定は当月。
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        $query = User::query()
            ->select(['id', 'name', 'email'])
            ->when(Schema::hasColumn('users', 'is_admin'), function ($q) {
                $q->where('is_admin', false);
            });

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.staff.index', [
            'users' => $users,
            'month' => $month,
        ]);
    }
}