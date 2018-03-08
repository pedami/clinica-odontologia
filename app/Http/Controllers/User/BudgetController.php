<?php

namespace App\Http\Controllers\User;

use App\Budget;
use App\BudgetDetail;
use App\Patient;
use App\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use PDF;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! empty($request->search)) {
            $patients = Patient::select('id')
                ->where('phone', 'LIKE', "%$request->search%")
                ->orWhere('name', 'LIKE', "%$request->search%")
                ->orderByDesc('id')
                ->get()
            ;

            $budgets = Budget::orderByDesc('id')->limit(15);

            foreach ($patients as $patient) {
                $budgets->orWhere('patient_id', $patient['id']);
            }

            $budgets = $budgets->paginate();

        } else {
            $budgets = Budget::orderByDesc('id')->paginate();
        }

        return view('user.budget.index', compact('budgets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();

        if (empty($user->logo) ||
            empty($user->business_name) ||
            empty($user->address) ||
            empty($user->email) ||
            empty($user->phone)) {

            $this->sessionMessage('message.config.business.remember', self::ALERT_DANGER);

            return redirect()->route('config');
        }

        $products = Product::all();

        return view('user.budget.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        $budget = new Budget($request->all());
        $budget->public_id = Budget::nextPublicId();

        $budget->save();

        foreach ($request->details as $detail) {

            $budgetDetail = new BudgetDetail();
            $budgetDetail->budget_id = $budget->id;
            $budgetDetail->product_id = $detail['product_id'];
            $budgetDetail->quantity = $detail['quantity'];
            $budgetDetail->price = $detail['price'];
            $budgetDetail->save();
        }

        $this->sessionMessage('message.budget.create');
        Session::flash('pdf', $budget->public_id);

        DB::commit();

        return new JsonResponse(['success' => true, 'redirect' => route('budget.edit', ['budget' => $budget->public_id])]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $budget = Budget::where('public_id', $id)->with('budgetDetails')->first();

        if (! $budget) {
            abort(404);
        }

        $budget->details = $budget->budgetDetails;
        $budget->client = $budget->patient;
        $products = Product::all();

        return view('user.budget.edit', compact('budget', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $budget = Budget::where('public_id', $id)->with('budgetDetails')->firstOrFail();
        $budget->update($request->all());

        $budget->budgetDetails()->delete();

        foreach ($request->details as $detail) {
            $budgetDetail = new BudgetDetail($detail);
            $budgetDetail->budget_id = $budget->id;
            $budgetDetail->save();
        }

        DB::commit();

        $this->sessionMessage('message.budget.update');

        return new JsonResponse([
            'success' => true,
            'redirect' => route('budget.edit', ['budget' => $id])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        abort(404);
    }

    /**
     * Genera un pdf de una cotizacion
     *
     * @param $id
     * @return mixed
     */
    public function generatePdf($id)
    {
        $budget = Budget::where('public_id', $id)->first();

        if (! $budget) {
            abort(404);
        }

        $pdf = PDF::loadView('user.budget.pdf', compact('budget'));

        return $pdf->stream();
    }

    /**
     * Descarga un pdf de una cotizacion
     *
     * @param $id
     * @return mixed
     */
    public function downloadPdf($id)
    {
        $budget = Budget::where('public_id', $id)->first();

        if (! $budget) {
            abort(404);
        }

        $pdf = PDF::loadView('user.budget.pdf', compact('budget'));

        return $pdf->download('Cotizacion #' . $budget->public_id);
    }
}
