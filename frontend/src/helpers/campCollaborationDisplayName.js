import i18n from '@/plugins/i18n'

/**
 * Returns a display name for a camp collaboration based on its status
 */
export default function (campCollaboration) {
  let text = null

  if (campCollaboration.user === null) {
    text = campCollaboration.inviteEmail
  } else {
    text = campCollaboration.user().displayName
  }

  if (campCollaboration.status === 'inactive') {
    text += ' (' + i18n.tc('entity.campCollaboration.inactive') + ')'
  }

  return text
}
