declare module 'core/str' {

  /**
 * String request interface
 */
  interface StringRequest {
    key: string;
    component?: string;
    lang?: string;
    param?: object | string;
  }
  /**
   * Return a Promise that resolves to a string.
   *
   * If the string has previously been cached, then the Promise will be resolved immediately, otherwise it will be fetched
   * from the server and resolved when available.
   *
   * @param {string} key The language string key
   * @param {string} [component='core'] The language string component
   * @param {object|string} [param] The param for variable expansion in the string.
   * @param {string} [lang] The users language - if not passed it is deduced.
   * @return {Promise<string>} A Promise containing the translated string
   */
  export function get_string(key: string, component?: string, param?: object | string, lang?: string): Promise<string>;

  /**
   * Return a Promise that resolves to a string.
   *
   * If the string has previously been cached, then the Promise will be resolved immediately, otherwise it will be fetched
   * from the server and resolved when available.
   *
   * @param {string} key The language string key
   * @param {string} [component='core'] The language string component
   * @param {object|string} [param] The param for variable expansion in the string.
   * @param {string} [lang] The users language - if not passed it is deduced.
   * @return {Promise<string>} A Promise containing the translated string
   */
  export function getString(key: string, component?: string, param?: object | string, lang?: string): Promise<string>;

  /**
   * Make a batch request to load a set of strings.
   *
   * Any missing string will be fetched from the server.
   * The Promise will only be resolved once all strings are available, or an attempt has been made to fetch them.
   *
   * @param {StringRequest[]} requests List of strings to fetch
   * @return {Promise<string[]>} A native promise containing an array of the translated strings
   */
  export function getStrings(requests: StringRequest[]): Promise<string[]>;

  /**
   * Make a batch request to load a set of strings.
   *
   * Any missing string will be fetched from the server.
   * The Promise will only be resolved once all strings are available, or an attempt has been made to fetch them.
   *
   * @param {StringRequest[]} requests List of strings to fetch
   * @return {Promise<string[]>} A promise containing an array of the translated strings
   */
  export function get_strings(requests: StringRequest[]): Promise<string[]>;

  /**
   * Add a list of strings to the caches.
   *
   * This function should typically only be called from core APIs to pre-cache values.
   *
   * @param {Object[]} strings List of strings to fetch
   * @param {string} strings.key The string identifer to fetch
   * @param {string} strings.value The string value
   * @param {string} [strings.component='core'] The componet to fetch from
   * @param {string} [strings.lang=Config.language] The language to fetch a string for. Defaults to current page language.
   */
  export function cache_strings(strings: {
    key: string;
    value: string;
    component?: string;
    lang?: string;
  }[]): void;
}