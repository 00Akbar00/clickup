<template>
  <div class="comments-section">
    <!-- Comments List -->
    <div v-if="loadingComments" class="text-muted">Loading comments...</div>
    <div v-else-if="comments.length" class="comment-list" ref="commentListRef">
      <div v-for="comment in comments" :key="comment._id" class="comment-card">
        <small class="text-muted d-block mt-1">{{ comment.name }}:</small>
        <small class="comment-timestamp text-muted">
          {{ formatDate(comment.timestamp) }}
        </small>
        <p class="mb-1">{{ comment.comment }}</p>

        <div v-if="comment.files && comment.files.length" class="mt-2">
          <div
            v-for="file in comment.files"
            :key="file._id"
            class="mb-2 border p-2 rounded"
          >
            <template v-if="file.mimetype.startsWith('image/')">
              <img
                :src="file.path"
                :alt="file.originalname"
                class="comment-img d-block mb-1"
                style="max-width: 200px"
              />
            </template>
            <div class="d-flex align-items-center gap-2">
              <span>📎 {{ file.originalname }}</span>
              <a
                :href="file.path"
                :download="file.originalname"
                target="_blank"
                class="btn btn-sm btn-outline-secondary"
              >
                Download
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <p v-else class="text-muted">No comments yet.</p>

    <!-- New Comment Form -->
    <form @submit.prevent="submitComment" enctype="multipart/form-data" class="mt-3">
      <textarea
        v-model="newComment"
        placeholder="Write a comment..."
        class="form-control mb-2"
        rows="2"
        required
      ></textarea>

      <input type="file" multiple @change="handleFile" class="form-control mb-2" />

      <AppButton
        :label="'Comment'"
        :loading="loadingSubmit"
        btnClass="btn-primary"
        small
      />
    </form>
  </div>
</template>



<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'
import AppButton from '../components/LoadingButton.vue'


const props = defineProps({
  taskId: String
})

const comments = ref([])
const newComment = ref('')
const files = ref([])
const commentListRef = ref(null)
const loadingComments = ref(true)
const loadingSubmit = ref(false)

const token = localStorage.getItem('authToken')

// Connect to Socket.IO
const socket = window.io('http://localhost:3001', {
  transports: ['websocket', 'polling']
})

const fetchComments = async () => {
  try {
    const res = await axios.get(
      `http://localhost/api/tasks/${props.taskId}/comments`,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    )
    comments.value = res.data
    await nextTick()
    const el = commentListRef.value
    if (el) el.scrollTop = el.scrollHeight
  } catch (err) {
    console.error('Error fetching comments', err)
  } finally {
    loadingComments.value = false
  }
}

const handleFile = (e) => {
  files.value = Array.from(e.target.files)
}

const submitComment = async () => {
  if (!newComment.value.trim()) return
  loadingSubmit.value = true
  const formData = new FormData()
  formData.append('comment', newComment.value)
  files.value.forEach((f) => {
    formData.append('files[]', f)
  })

  try {
    await axios.post(
      `http://localhost/api/tasks/${props.taskId}/comments`,
      formData,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json'
        }
      }
    )
    newComment.value = ''
    files.value = []
  } catch (err) {
    console.error('Error posting comment', err)
  } finally {
    loadingSubmit.value = false
  }
}

const formatDate = (timestamp) => {
  if (!timestamp) return ''
  return new Date(timestamp).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

onMounted(() => {
  fetchComments()

  socket.on('connect', () => {
    socket.emit('join_task', props.taskId)
  })

  socket.on('new_comment', async (comment) => {
    comments.value.push(comment)
    await nextTick()
    const el = commentListRef.value
    if (el) el.scrollTop = el.scrollHeight
  })
})

onUnmounted(() => {
  socket.off('connect')
  socket.off('new_comment')
  socket.disconnect()
})
</script>



<style scoped>
.comments-section {
  max-height: 70vh;
  overflow-y: auto;
}

.comment-list {
  max-height: 300px;
  overflow-y: auto;
  padding-right: 10px;
  margin-bottom: 1rem;
}
.comment-card {
  background-color: #f9f9f9;
  border-radius: 8px;
  padding: 10px 10px 24px 10px; /* extra bottom padding */
  margin-bottom: 10px;
  position: relative;
  word-break: break-word; /* ensures long words break instead of overflow */
}
.comment-timestamp {
  position: absolute;
  bottom: 6px;
  right: 10px;
  font-size: 0.70rem;
  white-space: nowrap;
}
.comment-img {
  max-width: 80px !important;
  border-radius: 5px;
  display: block;
}

</style>
