/**
 * WP Hooks interface
 * @ref https://www.npmjs.com/package/@wordpress/hooks
 */
export default interface WPHooks {
	createHooks(): void;
	addAction( hookName: string, namespace: string, callback: Function, priority?: number ): void;
	addFilter( hookName: string, namespace: string, callback: Function, priority?: number ): void;
	removeAction( hookName: string, namespace: string ): boolean;
	removeFilter( hookName: string, namespace: string ): boolean;
	removeAllActions( hookName: string ): boolean;
	removeAllFilters( hookName: string ): boolean;
	doAction( hookName: string, ...args: any ): void;
	applyFilters( hookName: string, content: any, ...args: any ): any;
	doingAction( hookName: string ): boolean;
	doingFilter( hookName: string ): boolean;
	didAction( hookName: string ): boolean;
	didFilter( hookName: string ): boolean;
	hasAction( hookName: string, namespace: string ): boolean;
	hasFilter( hookName: string, namespace: string ): boolean;
	actions: string[];
	filters: string[];
}