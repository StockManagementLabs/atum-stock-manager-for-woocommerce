import { IMenuItem } from './menu.interface';

/**
 * Interfaces for the ListTables data and components' HTML
 */

export interface ITableData {
	rows: string;
	extraTableNav: {
		top: string;
		bottom: string;
	};
	columnHeaders: string;
	views: string;
	paged?: number;
	totals?: string;
	totalItemsI18n?: string;
	totalPages?: string;
	totalPagesI18n?: string;
	rowActions?: IMenuItem[];
}