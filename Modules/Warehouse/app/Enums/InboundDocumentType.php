<?php

namespace Modules\Warehouse\Enums;

enum InboundDocumentType: string
{
    case VatInvoice         = 'vat_invoice';
    case CustomsDeclaration = 'customs_declaration';
    case HandoverRecord     = 'handover_record';
    case BillOfLading       = 'bill_of_lading';

    public function label(): string
    {
        return match ($this) {
            self::VatInvoice         => 'Hóa đơn GTGT (VAT)',
            self::CustomsDeclaration => 'Tờ khai hải quan',
            self::HandoverRecord     => 'Biên bản bàn giao',
            self::BillOfLading       => 'Vận đơn (Bill of Lading)',
        };
    }
}
