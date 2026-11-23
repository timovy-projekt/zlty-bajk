<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BikeController extends Controller
{
    /**
     * Bicycle statuses available in the database.
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'ready' => 'Pripravený',
        'maintenance' => 'V servise',
        'retired' => 'Vyradený',
        'lost' => 'Stratený',
    ];

    /**
     * Page size options available in the bicycle list.
     *
     * @var list<int>
     */
    public const array PER_PAGE_OPTIONS = [10, 20, 50, 100];

    /**
     * Display a searchable, paginated list of bicycles.
     */
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();
        $perPage = $request->integer('per_page', self::PER_PAGE_OPTIONS[0]);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $bikes = DB::table('bikes')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('inventory_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->when(array_key_exists($status, self::STATUSES), fn (Builder $query): Builder => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.bikes.index', [
            'bikes' => $bikes,
            'search' => $search,
            'status' => $status,
            'statuses' => self::STATUSES,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }
}
