import uuidv4 from 'uuid-v4';
import Symbol from 'symbol';

/**
 * Check whether current variable is object or not
 * @param  {Mixed}  value Object variable
 * @return {Boolean}      True if parameter is object
 */
export const isObject = function(value) {
  return (typeof value === 'undefined' ? 'undefined' : typeof value) === 'object' && value !== null;
};
/**
 * Extend the given object.
 * @param {*} obj - The object to be extended.
 * @param {*} args - The rest objects which will be merged to the first object.
 * @return {Object} The extended object.
 */
export const assign = Object.assign || function assign(obj, ...argss) {
  let len;
  let args;
  let key;
  for (len = argss.length, args = Array(len > 1 ? len - 1 : 0), key = 1; key < len; key++) {
    args[key - 1] = argss[key];
  }

  if (isObject(obj) && args.length > 0) {
    args.forEach(function(arg) {
      if (isObject(arg)) {
        Object.keys(arg).forEach(function(key) {
          obj[key] = arg[key];
        });
      }
    });
  }

  return obj;
};
/**
 * Match the values from a string or array against a str.
 * @param  {String} str    String we are searching
 * @param  {String|Array}  filter String or array of values to match
 * @return {Boolean}        [description]
 */
export const match = (str = '', filter) => {
  if (!filter) {
    console.warn('utils.match missing argument 2.');
    return false;
  }
  const matchOperators = /[|\\{}()[\]^*$+?.]/g;
  let filterArray = (typeof filter === 'string') ? [filter] : filter;
  filterArray = filterArray.map(filterStr => {
    return filterStr === '*' ? '' : filterStr.replace(matchOperators, '\\$&');
  });

  let isMatch = true;
  if (filterArray.length) {
    isMatch = !str.match(new RegExp(filterArray.join('|'), 'i'));
  }

  return isMatch;
};

/**
 * Removes a value from an array
 * @param  {Array} arr
 * @param  {String|Number} val
 */
export const remove = (arr, val) => {
  const index = arr.indexOf(val);

  if (index > -1) {
    arr.splice(index, 1);
  }
};

/**
 * Find the closest parent by class
 * @param  {Object} el  DOM element
 * @param  {String} cls class
 * @return {Object}     DOM Element
 */
export const closest = (el, cls) => {
  const className = cls.replace('.', '');
  while ((el = el.parentElement) && !el.classList.contains(className));
  return el;
};

/**
 * Find the closest parent by class
 * @param  {Object} el  DOM element
 * @param  {String} cls class
 * @return {Object}     DOM Element
 */
export const closestFtype = el => {
  while ((el = el.parentElement) && !el.fType);
  return el;
};

/**
 * Return tag and type of element
 * @param  {DOM} el DOM element
 * @return {Object}    tag and type
 */
export const elementTagType = el => {
  const element = {
    tag: el.tagName
  };
  if (el.tagName == 'INPUT') {
    element.type = el.type;
  }
  return element;
};

/**
 * Return string with first capital letter
 * @param  {String} string plain string
 * @return {String}        String with first letter capital
 */
export const ucfirst = string => {
  return string.charAt(0).toUpperCase() + string.slice(1);
};

/**
 * Remove duplicates from an array of elements
 * @param  {Array} array with possible duplicates
 * @return {Array} array with only unique values
 */
export const unique = array =>
  array.filter((elem, pos, arr) =>
    (arr.indexOf(elem) === pos));

export const objToStrMap = obj => {
  const strMap = new Map();
  Object.keys(obj).forEach(k => {
    strMap.set(k, obj[k]);
  });
  return strMap;
};

export const strMapToObj = strMap => {
  const obj = Object.create(null);
  strMap.forEach((v, k) => {
    obj[k] = v;
  });
  return obj;
};

/**
 * Return new unique id or id of element
 * @param  {DOM} elem DOM element
 * @return {String}      unique id
 */
export const uuid = elem => {
  let id;
  if (elem) {
    const {attrs = {}} = elem;
    id = attrs.id || elem.id || uuidv4();
    elem.id = id;
  } else {
    id = uuidv4();
  }
  return id;
};

/**
 * Clone object and return its copy
 * @param  {Object} obj Object to be cloned
 * @return {Object}     Cloned object
 */
export const clone = obj => {
  let copy;

  // Handle the 4 simple types, and null or undefined
  if (null === obj || 'object' !== typeof obj) {
    return obj;
  }

  // Handle Date
  if (obj instanceof Date) {
    copy = new Date();
    copy.setTime(obj.getTime());
    return copy;
  }

  // Handle Array
  if (obj instanceof Array) {
    copy = [];
    for (let i = 0, len = obj.length; i < len; i++) {
      copy[i] = clone(obj[i]);
    }
    return copy;
  }

  // Handle Object
  if (obj instanceof Object) {
    copy = {};
    for (const attr in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, attr)) {
        copy[attr] = clone(obj[attr]);
      }
    }
    return copy;
  }
  return obj;
};

export const strip = html => {
  const temp = document.createElement('DIV');
  temp.innerHTML = html;
  return temp.textContent || temp.innerText || '';
};

/**
 * Att title attribute to element object
 * @param  {Object} obj    Element object
 * @param  {String} string title string id
 * @return {Object}        Object with title
 */
export const addTitle = (obj, string) => {
  obj = clone(obj);
  obj.attrs.title = getString(string);
  return obj;
};

export const numToPercent = num => num.toString() + '%';

export const numberBetween = (num, min, max) => (num > min && num < max);

/**
 * Empty an objects contents
 * @param  {Object} obj
 * @return {Object} fresh
 */
export const cleanObj = obj => {
  const fresh = Object.assign({}, obj);
  Object.keys(obj).forEach(key => {
    if (typeof obj[key] === 'string') {
      fresh[key] = key;
    } else if (typeof obj[key] === 'boolean') {
      fresh[key] = false;
    }
  });
  return fresh;
};

export const clicked = (x, y, position, button) => {
  const xMin = position.x - 5;
  const xMax = position.x + 5;
  const yMin = position.y - 5;
  const yMax = position.y + 5;
  const xOK = numberBetween(x, xMin, xMax);
  const yOK = numberBetween(y, yMin, yMax);

  return (xOK && yOK && button !== 2);
};

/**
 * Return string from local_edwiserform or id
 * @param {String} id of string
 * @param {String/Array} args arguments for get_string function
 * @return {String} string
 */
export const getString = (id, args = null) => {
  const M = window.M;
  const string = args === null ? M.util.get_string(id, 'local_edwiserform') : M.util.get_string(id, 'local_edwiserform', args);
  if (string == '[[' + id + ',local_edwiserform]]') {
    id = id.replace('-', ' ');
    if (string.indexOf('.') != -1) {
      id = id.split('.');
      id = id.slice(id.length - 1)[0];
    }
    id = id.charAt(0).toUpperCase() + id.slice(1);
    return id;
  }
  return string;
};

/**
 * Hide control list element
 * @param  {String} className of control element
 */
export const hideControl = className => {
  const control = document.querySelector('.' + className);
  const panel = control.parentElement.parentElement;
  const list = control.parentElement;
  const previousHeight = list.clientHeight + 'px';
  control.classList.add('disable-control');
  if (!panel.classList.contains('collapsed-controls')) {
    list.style.height = 'auto';
    const height = list.clientHeight + 'px';
    list.style.height = previousHeight;
    setTimeout(() => {
      list.style.height = height;
    }, 0);
  }
};

/**
 * Show control list element
 * @param  {String} className of control element
 */
export const showControl = className => {
  const control = document.querySelector('.' + className);
  const panel = control.parentElement.parentElement;
  const list = control.parentElement;
  const previousHeight = list.clientHeight + 'px';
  control.classList.remove('disable-control');
  if (!panel.classList.contains('collapsed-controls')) {
    list.style.height = 'auto';
    const height = list.clientHeight + 'px';
    list.style.height = previousHeight;
    setTimeout(() => {
      list.style.height = height;
    }, 0);
  }
};

/**
 * Cache results of expensive functions
 * @param  {Function} fn
 * @param  {Function} resolver
 * @return {String|Object} memoized
 */
export const memoize = (fn, resolver) => {
  if (typeof fn !== 'function' ||
    (resolver && typeof resolver !== 'function')) {
    throw new TypeError('memoize: First argument must be a function');
  }
  const memoized = (...args) => {
    const key = resolver ? resolver.apply(memoized, args) : args[0];
    const cache = memoized.cache;

    if (cache.has(key)) {
      return cache.get(key);
    }
    const result = fn.apply(memoized, args);
    memoized.cache = cache.set(key, result);
    return result;
  };
  memoized.cache = new (memoize.Cache)();
  return memoized;
};

// Assign cache to `_.memoize`.
memoize.Cache = Map;
