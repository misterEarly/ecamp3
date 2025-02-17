<!--
Listing all given activity schedule entries in a calendar view.
-->

<template>
  <div>
    <v-calendar
      ref="calendar"
      v-model="value"
      v-resize="resize"
      :class="editable ? 'ec-picasso-editable' : 'ec-picasso'"
      :events="scheduleEntries"
      :event-name="getActivityName"
      :event-color="getActivityColor"
      event-start="startTime"
      event-end="endTime"
      :interval-height="computedIntervalHeight"
      interval-width="46"
      :interval-format="intervalFormat"
      :first-interval="firstInterval"
      :interval-count="intervalCount"
      :interval-minutes="intervalMinutes"
      :start="start"
      :end="end"
      :locale="$i18n.locale"
      :day-format="dayFormat"
      :type="type"
      :max-days="maxDays"
      :weekday-format="weekdayFormat"
      :weekdays="[1, 2, 3, 4, 5, 6, 0]"
      color="primary"
      :event-ripple="false"
      v-on="vCalendarListeners"
      @mouseleave.native="onMouseleave"
      @mousedown.native.prevent="/*this prevents from middle button to start scroll behavior*/">
      <!-- day header -->
      <template #day-label-header="{ date }">
        <div class="ec-daily_head-day-label">
          <span v-if="widthPluralization > 0" class="d-block">
            {{ $date.utc(date).format('dddd') }}
          </span> {{ $date.utc(date).format($tc('components.camp.picasso.datetime.date', widthPluralization)) }}
        </div>
        <day-responsibles
          :date="date"
          :period="period"
          :disabled="!editable"
          class="mt-1 mx-2" />
      </template>

      <!-- template for single scheduleEntry -->
      <template #event="{event, timed}">
        <!-- edit button & dialog -->
        <dialog-activity-edit
          v-if="editable && !event.tmpEvent"
          :ref="`editDialog-${event.id}`"
          :schedule-entry="event"
          @activityUpdated="reloadScheduleEntries()"
          @error="reloadScheduleEntries()">
          <template #activator="{ on }">
            <v-btn absolute
                   top
                   right x-small
                   dark text
                   class="ec-event--btn rounded-sm"
                   @click.prevent="on.click"
                   @mousedown.stop=""
                   @mouseup.stop="">
              <v-icon x-small>mdi-pencil</v-icon>
            </v-btn>
          </template>
        </dialog-activity-edit>

        <!-- readonly mode: complete div is a HTML link -->
        <router-link v-if="!editable && !event.tmpEvent" :to="scheduleEntryRoute(event)">
          <div class="readonlyEntry">
            <h4 class="v-event-title">
              {{ getActivityName(event) }}
            </h4>
          </div>
        </router-link>

        <!-- edit mode: normal div with drag & drop -->
        <div v-if="editable" class="editableEntry">
          <h4 class="v-event-title">
            {{ getActivityName(event) }}
          </h4>

          <!-- resize handle -->
          <div
            v-if="editable && timed"
            class="v-event-drag-bottom"
            @mousedown.stop="startResize(event)" />
        </div>
      </template>
    </v-calendar>

    <v-snackbar v-model="isSaving" light>
      <template v-if="patchError">
        <v-icon>mdi-alert</v-icon>
        {{ patchError }}
      </template>
      <template v-else>
        <v-icon class="mdi-spin">mdi-loading</v-icon>
        {{ $tc('global.button.saving') }}
      </template>
    </v-snackbar>
  </div>
</template>
<script>
import { toRefs, ref } from '@vue/composition-api'
import useDragAndDropMove from './useDragAndDropMove.js'
import useDragAndDropResize from './useDragAndDropResize.js'
import useDragAndDropNew from './useDragAndDropNew.js'
import useClickDetector from './useClickDetector.js'
import { isCssColor } from 'vuetify/lib/util/colorUtils'
import { apiStore as api } from '@/plugins/store'
import { scheduleEntryRoute } from '@/router.js'
import mergeListeners from '@/helpers/mergeListeners.js'

import DialogActivityEdit from '@/components/scheduleEntry/DialogActivityEdit.vue'
import DayResponsibles from './DayResponsibles.vue'

export default {
  name: 'Picasso',
  components: {
    DialogActivityEdit,
    DayResponsibles
  },
  props: {
    // period for which to show picasso
    period: {
      type: Object,
      required: true
    },

    // list of scheduleEntries
    scheduleEntries: {
      type: Array,
      required: true
    },

    // false disables drag & drop and disabled edit dialogs
    editable: {
      type: Boolean,
      required: false,
      default: false
    },

    // v-calendar start: starting date (first day)
    start: {
      type: Number,
      required: true
    },

    // v-calender end: end date (last day)
    end: {
      type: Number,
      required: true
    },

    // v-calendar type
    type: {
      type: String,
      required: false,
      default: 'custom-daily'
    },

    // v-calendar intervalHeight
    intervalHeight: {
      type: Number,
      required: false,
      default: null
    }

  },

  // emitted events
  emits: [
    'changePlaceholder', // triggered continuously while a new entry is being dragged (parameters: startTime, endTime)

    'newEntry' // triggered once when a new entry was created via drag & drop (parameters: startTime, endTime)
  ],

  // composition API setup
  setup (props, { emit, refs }) {
    const { editable } = toRefs(props)

    const isSaving = ref(false)
    const patchError = ref(null)

    // callback used to save entry to API
    const updateEntry = (scheduleEntry, periodOffset, length) => {
      const patchData = {
        periodOffset,
        length
      }
      isSaving.value = true
      api.patch(scheduleEntry._meta.self, patchData).then(() => {
        patchError.value = null
        isSaving.value = false
      }).catch((error) => {
        patchError.value = error
      })
    }

    // open edit dialog
    const onClick = (scheduleEntry) => {
      refs[`editDialog-${scheduleEntry.id}`].open()
    }

    const dragAndDropMove = useDragAndDropMove(editable, 5, updateEntry)
    const dragAndDropResize = useDragAndDropResize(editable, updateEntry)
    const dragAndDropNew = useDragAndDropNew(editable, emit)
    const clickDetector = useClickDetector(editable, 5, onClick)

    // merge mouseleave handlers
    // this is needed, because .native modifiers doesn't work with v-on property
    // https://github.com/vuejs/vue/issues/5578#issuecomment-516932359
    const onMouseleave = () => {
      dragAndDropMove.nativeMouseLeave()
      dragAndDropResize.nativeMouseLeave()
      dragAndDropNew.nativeMouseLeave()
    }

    // merge v-calendar listeners
    const vCalendarListeners = mergeListeners([
      dragAndDropMove.vCalendarListeners,
      dragAndDropResize.vCalendarListeners,
      dragAndDropNew.vCalendarListeners,
      clickDetector.vCalendarListeners
    ])

    return {
      vCalendarListeners,
      startResize: dragAndDropResize.startResize,
      onMouseleave,
      isSaving,
      patchError
    }
  },
  data () {
    return {
      maxDays: 100,
      entryWidth: 80,
      value: '',
      activitiesLoading: true,
      categoriesLoading: true,

      // interval configuration for v-calendar
      // only 0-24 supported at the moment, until https://github.com/vuetifyjs/vuetify/issues/14603 is resolved
      intervalMinutes: 60,
      firstInterval: 0,
      intervalCount: 24
    }
  },
  computed: {
    widthPluralization () {
      if (this.entryWidth < 81) {
        return 0
      } else if (this.entryWidth < 85) {
        return 1
      } else {
        return 2
      }
    },
    camp () {
      return this.period.camp()
    },
    computedIntervalHeight () {
      return this.intervalHeight ??
        this.$vuetify.breakpoint.xsOnly
        ? 1.3 * (this.$vuetify.breakpoint.height - 140) / this.intervalCount
        : 1.3 * Math.max((this.$vuetify.breakpoint.height - 204) / this.intervalCount, 32)
    }
  },
  mounted () {
    this.period.camp().activities()._meta.load.then(() => { this.activitiesLoading = false })
    this.period.camp().categories()._meta.load.then(() => { this.categoriesLoading = false })

    // scroll a bit down to hide the night hours
    const scroller = this.$el.querySelector('.v-calendar-daily__scroll-area')
    scroller.scrollTo({ top: 250 })
  },
  methods: {
    resize () {
      const widthIntervals = 46
      this.entryWidth = Math.max((this.$refs.calendar.$el.offsetWidth - widthIntervals) / this.$refs.calendar.days.length, 80)
    },
    getActivityName (scheduleEntry, _) {
      if (this.isActivityLoading(scheduleEntry)) return this.$tc('global.loading')
      return (scheduleEntry.number ? scheduleEntry.number + ' ' : '') +
        (scheduleEntry.activity().category().short ? scheduleEntry.activity().category().short + ': ' : '') +
        scheduleEntry.activity().title
    },
    getActivityColor (scheduleEntry, _) {
      if (this.isCategoryLoading(scheduleEntry)) return 'grey lighten-1'
      const color = scheduleEntry.activity().category().color
      return isCssColor(color) ? color : color + ' elevation-4 v-event--temporary'
    },
    isActivityLoading (scheduleEntry) {
      return !scheduleEntry.tmpEvent && (this.activitiesLoading || this.categoriesLoading || scheduleEntry.activity()._meta.loading)
    },
    isCategoryLoading (scheduleEntry) {
      return !scheduleEntry.tmpEvent && (this.categoriesLoading || this.activitiesLoading || scheduleEntry.activity()._meta.loading || scheduleEntry.activity().category()._meta.loading)
    },
    intervalFormat (time) {
      return this.$date.utc(time.date + ' ' + time.time).format(this.$tc('global.datetime.hourLong'))
    },
    dayFormat (day) {
      if (this.$vuetify.breakpoint.smAndDown) {
        return this.$date.utc(day.date).format(this.$tc('global.datetime.dateShort'))
      } else {
        return this.$date.utc(day.date).format(this.$tc('global.datetime.dateLong'))
      }
    },
    weekdayFormat () {
      return ''
    },
    reloadScheduleEntries () {
      this.api.reload(this.period.scheduleEntries())
    },
    scheduleEntryRoute
  }
}
</script>

<style scoped lang="scss">
.ec-picasso, .ec-picasso-editable {
  height:calc(100vh - 200px);

  @media #{map-get($display-breakpoints, 'xs-only')}{
      height:calc(100vh - 120px);
  }

  ::v-deep {
    .v-calendar-daily_head-day,
    .v-calendar-daily__day {
      min-width: 80px;
    }

    .v-event-timed {
      padding: 0px;
      font-size: 11px !important;
      white-space: normal;
      line-height: 1.15;
      user-select: none;
      -webkit-user-select: none;

      // full size div within v-calendar event
      div.readonlyEntry, div.editableEntry {
          width: 100%;
          height: 100%;
          left: 0;
          top: 0;
          padding:3px;
      }
    }
  }
}

/**
 * entry styling in edit mode
 */
.editableEntry {
  cursor: move; /* fallback if grab cursor is unsupported */
  cursor: grab;
  cursor: -moz-grab;
  cursor: -webkit-grab;
  border: 1px black dashed;
  border-radius: 4px;

  &:active {
    cursor: move;
    cursor: -moz-grabbing;
    cursor: -webkit-grabbing;
  }
}

.ec-picasso-editable {
  ::v-deep .v-event-timed {
    transition: transform .1s; /* Animation */
  }

  ::v-deep .v-event-timed:hover{
    transform: scale(1.02); /* (150% zoom - Note: if the zoom is too large, it will go outside of the viewport) */
  }

}

// entry edit button
.ec-event--btn {
  padding: 0 !important;
  min-width: 20px !important;
  top: 0 !important;
  right: 0 !important;
  display: block;
}

// event title text
.v-event-title {
  hyphens: auto;
  hyphenate-limit-chars: 6 3 3;
  hyphenate-limit-lines: 2;
  hyphenate-limit-last: always;
  hyphenate-limit-zone: 8%;
}

// day title
.ec-daily_head-day-label {
  font-size: 11px;
  font-feature-settings: "tnum";
  letter-spacing: -.1px;
}

// temporary placeholder (crate new event)
::v-deep .v-event-timed.v-event--temporary {
  border-style: dashed !important;
  opacity: .8;
}

// resize handle
.v-event-drag-bottom {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 4px;
  height: 4px;
  cursor: ns-resize;

  &::after {
    display: none;
    position: absolute;
    left: 50%;
    height: 4px;
    border-top: 1px solid white;
    border-bottom: 1px solid white;
    width: 16px;
    margin-left: -8px;
    opacity: 0.8;
    content: '';
  }
}

@media #{map-get($display-breakpoints, 'sm-and-up')}{
  .v-event-timed {
    &:hover .v-event-drag-bottom::after {
      display: block; // resize handle not visible on mobile
    }
  }
}

</style>
