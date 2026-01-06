{{-- Invalid: Legacy PHP tags that should be Blade syntax --}}
<table>
    <tr>
        <td><span>{{ date('d/m/Y H:i', strtotime($payment->created_at))}}</span>{{ date('d/m/Y',strtotime($payment->created_at))}}</td>
        <td><?= $payment->name ?></td>
        <td><?= $payment->iban ?></td>
        <td><?= "€" . \App\Http\Controllers\HelperController::factuurgetal($payment->amount) ?></td>
        <td><?= $payment->type ?></td>
        <td><?= $payment->announcement ?></td>
    </tr>
</table>

<?php
    $total = 0;
    foreach ($payments as $p) {
        $total += $p->amount;
    }
    echo "Total: €" . number_format($total, 2);
?>
