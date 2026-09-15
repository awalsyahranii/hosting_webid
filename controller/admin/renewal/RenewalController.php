<?php
// controller/admin/renewal/RenewalController.php

require_once __DIR__ . '/../../../models/admin/renewal/Renewal.php';

class RenewalController
{
    private $renewalModel;

    public function __construct()
    {
        $this->renewalModel = new Renewal();
    }

    /**
     * Get list of renewals with month, year, category, status filters & summary
     */
    public function getRenewals(array $filters = [])
    {
        $renewals = $this->renewalModel->getRenewals($filters);
        $summary = $this->renewalModel->getRenewalSummary(
            !empty($filters['month']) ? (int)$filters['month'] : null,
            !empty($filters['year']) ? (int)$filters['year'] : null
        );

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Data perpanjangan layanan berhasil diambil',
            'data' => [
                'renewals' => $renewals,
                'total' => count($renewals),
                'summary' => $summary,
                'filters' => $filters
            ]
        ];
    }

    /**
     * User submits renewal request
     */
    public function requestRenewal($userId, $orderItemId, $notes = '')
    {
        if (empty($userId)) {
            return ['status' => 'error', 'code' => 401, 'message' => 'User belum login'];
        }
        if (empty($orderItemId) || !is_numeric($orderItemId)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID layanan tidak valid'];
        }

        return $this->renewalModel->createRenewalRequest($userId, $orderItemId, $notes);
    }

    /**
     * Admin creates renewal invoice
     */
    public function createInvoice($orderItemId, $amount = null, $billingPeriod = null, $dueDate = null, $notes = null)
    {
        if (empty($orderItemId) || !is_numeric($orderItemId)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID layanan wajib disertakan'];
        }

        return $this->renewalModel->createRenewalInvoice($orderItemId, $amount, $billingPeriod, $dueDate, $notes);
    }

    /**
     * Admin confirms renewal invoice payment
     */
    public function confirmInvoice($invoiceId)
    {
        if (empty($invoiceId) || !is_numeric($invoiceId)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID invoice tidak valid'];
        }

        return $this->renewalModel->confirmRenewalInvoice($invoiceId);
    }

    /**
     * Get single invoice detail
     */
    public function getInvoiceDetail($invoiceId)
    {
        if (empty($invoiceId) || !is_numeric($invoiceId)) {
            return ['status' => 'error', 'code' => 400, 'message' => 'ID invoice tidak valid'];
        }

        $inv = $this->renewalModel->getInvoiceDetail($invoiceId);
        if (!$inv) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Invoice tidak ditemukan'];
        }

        return [
            'status' => 'success',
            'code' => 200,
            'message' => 'Detail invoice perpanjangan',
            'data' => $inv
        ];
    }
}
