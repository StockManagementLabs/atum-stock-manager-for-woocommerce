/**
 * Interfaces for the ListTables data and components' HTML
 */

export interface ITableData {
	rows: string;
	extra_t_n: {
		top: string;
		bottom: string;
	};
	column_headers: string;
	views: string;
	paged?: number;
	totals?: string;
	total_items_i18n?: string;
	total_pages?: string;
	total_pages_i18n?: string;
}