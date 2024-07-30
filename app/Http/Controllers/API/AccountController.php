<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    //

    public function sendmail(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $to = $request->input('to');
        $subject = $request->input('subject');
        $body = $request->input('body');

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            return $this->sendResponse([], 'Email sent successfully');
        } catch (\Exception $e) {
            return $this->sendError('Email could not be sent.', ['error' => $e->getMessage()]);
        }
    }


    public function createAccount(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return $this->sendError('User not authenticated', [], 401);
        }

        $data = $request->validate([
            'balance' => 'required|numeric|min:0',
            'account_type' => 'required|string',
        ]);

        $data['user_id'] = $userId;

        $account = Account::create($data);

        if (!$account) {
            return $this->sendError('Account creation failed', [], 500);
        }

        return $this->sendResponse($account, 'Account created successfully', 201);
    }

    public function updateAccount(Request $request, $accountId): JsonResponse
    {
        $userId = Auth::id();
        $account = Account::where('id', $accountId)->where('user_id', $userId)->first();

        if (!$account) {
            return $this->sendError('Account not found', [], 404);
        }

        $data = $request->validate([
            'balance' => 'sometimes|numeric|min:0',
            'account_type' => 'sometimes|string',
        ]);

        // Attempt to update the account with the provided data
        try {
            $account->fill($data)->save();
        } catch (\Exception $e) {
            // If an exception occurs, return an error response
            return $this->sendError('Account update failed: ' . $e->getMessage(), [], 500);
        }

        // Check if any changes were made
        if ($account->wasChanged()) {
            return $this->sendResponse($account, 'Account updated successfully');
        } else {
            return $this->sendResponse($account, 'No changes were made to the account');
        }
    }

    public function deleteAccount($accountId): JsonResponse
    {
        $userId = Auth::id();
        $account = Account::where('id', $accountId)->where('user_id', $userId)->first();

        if (!$account) {
            return $this->sendError('Account not found', [], 404);
        }

        $deleted = $account->delete();

        if (!$deleted) {
            return $this->sendError('Account deletion failed', [], 500);
        }

        return $this->sendResponse(null, 'Account deleted successfully');
    }

    public function viewAccount($accountId): JsonResponse
    {
        $userId = Auth::id();
        $account = Account::where('id', $accountId)->where('user_id', $userId)->first();

        if (!$account) {
            return $this->sendError('Account not found', [], 404);
        }

        return $this->sendResponse($account, 'Account retrieved successfully');
    }

    public function allAccounts(): JsonResponse
    {
        $userId = Auth::id();
        $accounts = Account::where('user_id', $userId)->get();

        return $this->sendResponse($accounts, 'Accounts retrieved successfully');
    }

    public function createTransaction(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'required|in:deposit,withdrawal',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->all(), 422);
        }

        $data = $validator->validated();

        $account = Account::where('id', $data['account_id'])->where('user_id', $userId)->first();
        if (!$account) {
            return $this->sendError('Unauthorized access to account', [], 403);
        }

        $transaction = Transaction::create($data);

        if (!$transaction) {
            return $this->sendError('Transaction creation failed', [], 500);
        }

        return $this->sendResponse($transaction, 'Transaction created successfully', 201);
    }

    public function deleteTransaction($transactionId): JsonResponse
    {
        $userId = Auth::id();
        $transaction = Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->find($transactionId);

        if (!$transaction) {
            return $this->sendError('Transaction not found or access denied', [], 404);
        }

        $deleted = $transaction->delete();

        if (!$deleted) {
            return $this->sendError('Transaction deletion failed', [], 500);
        }

        return $this->sendResponse(null, 'Transaction deleted successfully');
    }

    public function viewTransaction($transactionId): JsonResponse
    {
        $userId = Auth::id();
        $transaction = Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->find($transactionId);

        if (!$transaction) {
            return $this->sendError('Transaction not found or access denied', [], 404);
        }

        return $this->sendResponse($transaction, 'Transaction retrieved successfully');
    }

    public function allTransactions(): JsonResponse
    {
        $userId = Auth::id();
        $transactions = Transaction::whereHas('account', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();

        return $this->sendResponse($transactions, 'Transactions retrieved successfully');
    }

}
