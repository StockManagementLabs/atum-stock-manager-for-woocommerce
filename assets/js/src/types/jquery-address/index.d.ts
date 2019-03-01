/// <reference path="../../../../../node_modules/@types/jquery/index.d.ts" />

interface JQuery {
	address: any;
}

interface JQueryStatic {
	address: JQueryAddress;
}

/**
 * Type generated following official documentation
 * http://www.asual.com/jquery/address/docs/
 */
interface JQueryAddress {
	bind(type: string, data: any, fn: Function): any;
	change(fn: Function): any;
	init(fn: Function): any;
	internalChange(fn: Function): any;
	externalChange(fn: Function): any;
	baseURL(fn: Function): string;
	autoUpdate(): boolean;
	crawable(value?: any): any;
	hash(value?: string): any;
	history(value?: string): any;
	parameter(param: string, value?: any, append?: boolean): any;
	parameterNames(): string[];
	path(value?: string): any;
	pathNames(): string[];
	queryString(value?: string): any;
	state(value?: string): any;
	strict(value?: string): any;
	title(value?: string): any;
	tracker(value?: string): any;
	value(value?: string): any;
	wrap(value?: string): any;
}