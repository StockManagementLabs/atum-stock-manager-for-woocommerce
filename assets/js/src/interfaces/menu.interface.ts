/**
 * Interfaces for the MenuPopover
 */

export interface Menu {
	title?: string;
	items: MenuItem[];
}

export interface MenuItem {
	name: string;
	label: string;
	link?: string;
	icon?: string;
	data?: any;
}