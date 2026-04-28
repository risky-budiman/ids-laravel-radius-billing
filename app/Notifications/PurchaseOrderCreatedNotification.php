<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PurchaseOrderCreatedNotification extends Notification
{
    use Queueable;

    protected $purchaseOrder;

    public function __construct($purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'purchase_order_id' => $this->purchaseOrder->id,
            'subject' => 'Purchase Order Baru: ' . $this->purchaseOrder->po_number,
            'message' => "PO Baru senilai Rp " . number_format($this->purchaseOrder->total_amount, 0, ',', '.') . " menunggu peninjauan Anda.",
            'url' => route('purchase-orders.index'),
        ];
    }
}
