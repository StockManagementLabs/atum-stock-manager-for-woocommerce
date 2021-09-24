/**
 * Interfaces for the MenuPopover
 */

export interface IMenu {
	title?: string;
	items: IMenuItem[];
}

export interface IMenuItem {
	name: string;
	label: string;
	link?: string;
	icon?: string;
	data?: any;
}