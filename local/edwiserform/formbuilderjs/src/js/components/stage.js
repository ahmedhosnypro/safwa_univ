import Sortable from 'sortablejs';
import {data, formData, registeredFields as rFields} from '../common/data';
import h from '../common/helpers';
import dom from '../common/dom';
import {uuid, getString} from '../common/utils';

let stageOpts = {};

/**
 * Stage is where fields and elements are dragged to.
 */
export default class Stage {
  /**
   * Process options and load existing fields from data to the stage
   * @param  {Object} formeoOptions
   * @param  {String} stageID uuid
   * @return {Object} DOM element
   */
  constructor(formeoOptions, stageID) {
    this.stageID = stageID || uuid();
    const stage = formData.stages.get(this.stageID);
    let title = getString('stepindex');
    title = title.replace('{{index}}', (formData.stages.size + 1));
    if (stage) {
      title = stage.title;
    }
    const defaultOptions = {
      title: title,
      formSettings: [{
        tag: 'input',
        id: 'form-title',
        attrs: {
          className: 'form-title',
          placeholder: getString('UntitledForm'),
          value: title,
          type: 'text'
        },
        config: {
          label: getString('FormTitle')
        }
      }, {
        tag: 'input',
        id: 'form-novalidate',
        attrs: {
          className: 'form-novalidate',
          value: false,
          type: 'checkbox'
        },
        config: {
          label: getString('Formnovalidate')
        }
      }, {
        tag: 'input',
        id: 'form-tags',
        attrs: {
          className: 'form-tags',
          type: 'text'
        },
        config: {
          label: getString('Tags')
        }
      }]
    };

    stageOpts = Object.assign(stageOpts, defaultOptions, formeoOptions);
    if (!formData.stages.get(this.stageID)) {
      const defaultStageData = {
        title: stageOpts.title,
        id: this.stageID,
        settings: {},
        rows: []
      };
      formData.stages.set(this.stageID, defaultStageData);
    }

    const stageWrap = this.loadStage();

    return stageWrap;
  }

  /**
   * Prep stage to receive rows
   * @return {Object} DOM element
   */
  loadStage() {
    const _this = this;
    const stageWrap = this.dom;
    const sortable = Sortable.create(stageWrap.childNodes[0], {
      animation: 150,
      scroll: true,
      scrollSensitivity: 1000,
      direction: 'horizontal',
      fallbackClass: 'row-moving',
      forceFallback: h.isFireFoxEdge(),
      fallbackTolerance: 0,
      group: {name: 'stages', pull: true, put: [
        'controls',
        'rows',
        'columns'
      ]},
      // Element is dropped into the list from another list
      onAdd: _this.onAdd.bind(_this),
      onRemove: _this.onRemove.bind(_this),
      // OnDrop: _this.onAdd.bind(_this),
      sort: true,
      onStart: evt => {
        dom.activeStage = _this.stage;
      },
      onUpdate: evt => {
        data.saveRowOrder();
        data.save();
      },
      onSort: _this.onSort,
      draggable: '.stage-rows',
      handle: '.item-handle'
    });
    dom.stages.set(this.stageID, {
      stage: this.stage,
      sortable
    });
    dom.activeStage = this.stage;
    if (formData.stages.get(this.stageID).rows.length) {
      dom.loadRows(this.stage);
    }

    return stageWrap;
  }

  /**
   * Generate the elements that make up the Stage
   * @return {Object} stage elements, settings, stage ul
   */
  elementConfigs() {
    const _this = this;
    const title = formData.stages.get(this.stageID).title || getString('untitled');
    const config = {
      header: {
        tag: 'label',
        content: title,
        attrs: {
          id: 'for-' + _this.stageID,
          contenteditable: true
        },
        className: 'stage-label',
        action: {
          focusout: (evt) => {
            let stageID = evt.target.id;
            const stageLabel = evt.target.innerText || evt.target.textContent;
            stageID = stageID.replace('for-', '');
            data.saveStageLabel(stageID, stageLabel);
          }
        }
      },
      stage: {
        tag: 'ul',
        attrs: {
          className: [
            'stage',
            'empty-stages'
          ],
          id: _this.stageID
        },
        fType: 'stages',
        content: []
      },
      settings: {
        tag: 'div',
        attrs: {
          className: 'formeo-settings',
          id: `${_this.stageID}-settings`
        },
        fType: 'settings'
      }
    };

    config.settings.content = stageOpts.formSettings.slice();

    return config;
  }

  /**
   * Callback for when a row is sorted
   * @param  {Object} evt
   */
  onSort(evt) {
    data.save();
  }

  /**
   * Method for handling stage drop
   * @param  {Object} evt
   * @return {Object} formData
   */
  onAdd(evt) {
    const _this = this;
    dom.activeStage = _this.stage;
    const {from, item, to} = evt;
    const newIndex = h.indexOfNode(item, to);
    if (!item.classList.contains('layout-tab-control')) {
      const row = from.fType === 'stages' ? item : dom.addRow();
      const fromColumn = from.fType === 'columns';
      const fromRow = from.fType === 'rows';
      let column;

      if (from.fType === 'controlGroup') {
        if (dom.license != 'available') {
          let text = evt.item.firstChild.lastChild.wholeText;
          text = getString('dragndrop', text);
          dom.proWarning(text);
          dom.remove(item);
          dom.remove(row);
          dom.emptyClass(dom.activeStage);
          return data.save();
        }
        const meta = rFields[item.id].meta;
        if (meta.group !== 'layout') {
          column = dom.addColumn(row.id);
          dom.addField(column.id, item.id, 'drag');
        } else if (meta.id === 'layout-column') {
          dom.addColumn(row.id);
        }
        dom.remove(item);
      } else if (fromColumn) {
        const column = dom.addColumn(row.id);
        column.appendChild(item);
        data.saveFieldOrder(column);
        dom.emptyClass(column);
      } else if (fromRow) {
        row.appendChild(item);
        data.saveColumnOrder(row);
        dom.emptyClass(row);
      }

      to.insertBefore(row, to.children[newIndex]);
      dom.columnWidths(row);
      data.saveRowOrder(to);
    } else {
      if (dom.license != 'available') {
        let text = evt.item.firstChild.lastChild.wholeText;
        text = getString('dragndrop', text);
        dom.proWarning(text);
        dom.remove(item);
        dom.emptyClass(dom.activeStage);
        return data.save();
      }
      formData.settings.set('tabSettings', dom.getTabDefaultConfigs()); // Adding empty setting in formdata
      dom.createTabContainer(item);
    }
    dom.checkSingle();
    return data.save();
  }

  /**
   * Handle removal of a row from stage
   * @param  {Object} evt
   * @return {Object} formData
   */
  onRemove(evt) {
    return data.save();
  }

  /**
   * Returns the markup for the stage
   *
   * @return {DOM}
   */
  get dom() {
    const _this = this;
    if (_this.stage) {
      return _this.stage;
    }
    const config = _this.elementConfigs();
    const stageWrap = dom.create({
      tag: 'div',
      attrs: {
        className: 'stage-wrap tab-pane',
        id: config.stage.attrs.id,
        role: 'tabpanel'
      },
      content: [
        config.stage,
        config.settings
      ],
      fType: 'stages'
    });
    _this.stage = stageWrap.childNodes[0];

    return stageWrap;
  }
}
