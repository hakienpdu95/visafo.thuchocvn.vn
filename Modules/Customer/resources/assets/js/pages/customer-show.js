import { initPartyContractsTable } from '@shared/party-contracts-table.js';

const EMPTY_TEXT = 'Chưa có hợp đồng nào với khách hàng này.';

let contractsTable = null;

window.onCustomerTabShown = function (tab) {
    if (tab === 'contracts') {
        if (!contractsTable) contractsTable = initPartyContractsTable('customer-contracts-table', EMPTY_TEXT);
        else contractsTable.redraw(true);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const initialTab = document.querySelector('[data-initial-tab]')?.dataset.initialTab;
    if (initialTab === 'contracts') contractsTable = initPartyContractsTable('customer-contracts-table', EMPTY_TEXT);
});
