import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useInboxStore = defineStore('inbox', () => {
  const activeTab = ref('Important')
  
  const importantMessages = ref([
    {
      id: 1,
      title: 'Project Deadline Approaching',
      content: 'The Marketing Campaign project is due in 2 days. Please review and complete pending tasks.',
      time: '2 hours ago'
    },
    {
      id: 2,
      title: 'High Priority Bug Report',
      content: 'Critical bug found in the checkout process. Immediate attention required.',
      time: '4 hours ago'
    },
    {
      id: 3,
      title: 'Team Meeting Reminder',
      content: 'Important team meeting tomorrow at 10 AM to discuss Q2 goals.',
      time: '5 hours ago'
    }
  ])

  const updates = ref([
    {
      id: 1,
      title: 'New Feature Released',
      content: 'Dark mode has been implemented across all pages. Check it out!',
      time: '1 day ago'
    },
    {
      id: 2,
      title: 'Project Status Update',
      content: 'Website redesign project is now 75% complete.',
      time: '2 days ago'
    },
    {
      id: 3,
      title: 'System Update',
      content: 'The system will undergo maintenance this weekend. Please save your work.',
      time: '3 days ago'
    }
  ])

  const directMessages = ref([
    {
      id: 1,
      title: 'Sarah mentioned you',
      content: 'Can you review the latest design mockups when you have a chance?',
      time: '1 hour ago'
    },
    {
      id: 2,
      title: 'John sent you a message',
      content: 'Thanks for helping with the documentation. It looks great!',
      time: '3 hours ago'
    },
    {
      id: 3,
      title: 'Team Design tagged you',
      content: 'Please provide your feedback on the new color scheme.',
      time: '1 day ago'
    }
  ])

  function setActiveTab(tab) {
    activeTab.value = tab
  }

  return {
    activeTab,
    importantMessages,
    updates,
    directMessages,
    setActiveTab
  }
}) 