<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use Filament\Actions;
use App\Models\Loans;
use App\Models\LoanRepayment;
use Filament\Resources\Pages\CreateRecord;

class CreateSalary extends CreateRecord
{
    protected static string $resource = SalaryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (strlen($data['salary_month']) == 7) {
            $data['salary_month'] .= '-01';
        }
        // self::handleLoanInstallment($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        self::handleLoanInstallment($this->record);
    }

    
    protected static function handleLoanInstallment($record)
    {
        $employeeId = $record['employee_id'];
        $installmentAmount = $record['loan_installment'];

        if ($installmentAmount > 0) {
            // Find active loan
            $loan = Loans::where('employee_id', $employeeId)
                        ->where('loan_status', 'Approved')
                        ->orderBy('created_at') // Optional, oldest loan first
                        ->first();

            if ($loan) {
                // Update loan info
                $loan->amount_paid += $installmentAmount;

                if (isset($record['total_due_loan']) && $record['total_due_loan'] <= 0) {
                    $loan->loan_status = 'Completed';
                }

                $loan->save();

                LoanRepayment::create([
                    'loan_id' => $loan->id,
                    'salary_id' => $record['id'],
                    'amount' => $installmentAmount,
                    'paid_at' => now(),
                    'salary_month' => $record['salary_month'], // optional
                ]);
            }
        }
    }

}
