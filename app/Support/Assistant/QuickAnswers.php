<?php

namespace App\Support\Assistant;

/**
 * Answers for the quick buttons, written straight from Tools results without
 * AI. They work even when every AI model is down or the daily limit is used.
 */
class QuickAnswers
{
    public static function answer(string $key): string
    {
        return match ($key) {
            'unpaid' => self::unpaid(),
            'vacant' => self::vacant(),
            'overview' => self::overview(),
            'receipts' => self::receipts(),
            'payments' => self::payments(),
            'issues' => self::issues(),
        };
    }

    private static function unpaid(): string
    {
        $data = Tools::unpaidBills();

        if ($data['unpaid_bill_count'] === 0) {
            return 'Bayad na tanan! Walay bill nga wala pa nabayran.';
        }

        $lines = collect($data['bills'])->map(fn ($bill) => "• {$bill['tenant']} (Room {$bill['room']}) – {$bill['balance']} para sa {$bill['bill_month']}".($bill['status'] === 'overdue' ? ', overdue' : ", due {$bill['due']}"));

        return "{$data['tenants_with_balance']} ka tenant ang naay balanse, total {$data['total_balance']}:\n".$lines->implode("\n");
    }

    private static function vacant(): string
    {
        $data = Tools::availableRooms();

        if ($data['count'] === 0) {
            return 'Wala nay bakante karon. Puno tanan nga kwarto.';
        }

        $lines = collect($data['rooms'])->map(fn ($room) => "• Room {$room['room']} ({$room['type']}) – {$room['free_slots']} ka bakante, {$room['monthly_rate']} kada bulan");

        return "Naa pay {$data['count']} ka kwarto nga naay bakante:\n".$lines->implode("\n");
    }

    private static function overview(): string
    {
        $data = Tools::overview();

        return implode("\n", [
            "Karon {$data['today']}:",
            "• Kwarto: {$data['rooms_occupied']} sa {$data['rooms_total']} ang occupied, {$data['rooms_with_free_slots']} ang naay bakante",
            "• Active nga tenant: {$data['active_tenants']}",
            "• Nakolekta karong bulana: {$data['collected_this_month']}",
            "• Wala pa nabayri: {$data['unpaid_bills']} ka bill, total {$data['unpaid_total']} ({$data['overdue_bills']} overdue)",
            "• Receipt nga wala pa na-check: {$data['receipts_waiting_review']}",
            "• Repairs nga wala pa nahuman: {$data['open_repairs']}, concerns: {$data['open_concerns']}",
        ]);
    }

    private static function receipts(): string
    {
        $data = Tools::pendingReceipts();

        if ($data['count'] === 0) {
            return 'Wala kay receipt nga kinahanglan i-check karon.';
        }

        $lines = collect($data['receipts'])->map(fn ($receipt) => "• {$receipt['tenant']} – {$receipt['amount']}, gi-upload {$receipt['uploaded']}");

        return "{$data['count']} ka receipt ang naghulat nimo sa Bills & Payments:\n".$lines->implode("\n");
    }

    private static function payments(): string
    {
        $data = Tools::recentPayments(7);

        if ($data['count'] === 0) {
            return 'Walay nibayad sulod sa miaging 7 ka adlaw.';
        }

        $lines = collect($data['payments'])->map(fn ($payment) => "• {$payment['tenant']} – {$payment['amount']} ({$payment['method']}) sa {$payment['date']}");

        return "{$data['count']} ka bayad sulod sa 7 ka adlaw, total {$data['total']}:\n".$lines->implode("\n");
    }

    private static function issues(): string
    {
        $data = Tools::openIssues();
        $repairs = collect($data['repairs']);
        $concerns = collect($data['concerns']);

        if ($repairs->isEmpty() && $concerns->isEmpty()) {
            return 'Walay repair o concern nga naghulat. Limpyo tanan!';
        }

        $lines = $repairs->map(fn ($repair) => "• Repair, Room {$repair['room']}: {$repair['issue']} ({$repair['status']}, {$repair['priority']})")
            ->merge($concerns->map(fn ($concern) => "• Concern ni {$concern['tenant']}: {$concern['subject']} ({$concern['status']})"));

        return "{$repairs->count()} ka repair ug {$concerns->count()} ka concern ang wala pa nahuman:\n".$lines->implode("\n");
    }
}
