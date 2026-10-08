<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\TransaksiRepository;
use App\Repositories\VoucherRepository;

class TransaksiService
{
    protected TransaksiRepository $transaksiRepository;
    protected EventRepository $eventRepostory;
    protected PaymentRepository $paymentRepository;
    protected VoucherRepository $voucherRepository;

    public function __construct(TransaksiRepository $transaksiRepository, EventRepository $eventRepostory, PaymentRepository $paymentRepository, VoucherRepository $voucherRepository)
    {
        $this->transaksiRepository = $transaksiRepository;
        $this->eventRepostory = $eventRepostory;
        $this->paymentRepository = $paymentRepository;
        $this->voucherRepository = $voucherRepository;
    }

    public function createTransaksi(array $data)
    {
        $event = $this->eventRepostory->findById($data['id_event']);
        $payment = $this->paymentRepository->findById($data['id_payment']);
        $voucher = $this->voucherRepository->findById($data['id_voucher']);

        $transaksi = $this->transaksiRepository->create([
            'id_event' => $data['id_event'],
            'invoice' => $data['invoice'],
            'jumlah_tiket' => $data['jumlah_tiket'],
            'total_pembayaran' => $data['total_pembayaran'],
            'name' => $data['name'],
            'email' => $data['email'],
            'telepon' => $data['telepon'],
            'status_pembayaran' => 'Pending',
            'tanggal_register' => now(),
            'tanggal_pembayaran' => null,
            'id_payment' => $data['id_payment'],
            'id_voucher' => $data['id_voucher'],
        ]);

        $this->eventRepostory->decreaseTicket($event, $data['jumlah_tiket']);

        return $transaksi;
    }
}