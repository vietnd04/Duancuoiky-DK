<script setup lang="ts">
import { ref, onMounted, computed, watchEffect } from 'vue'
import { useNotifications } from '../actions/getNotifications'
import { formatDistanceToNow } from 'date-fns'
import { vi } from 'date-fns/locale'
import { getUserData } from '../../../../helper/getUserData'

const { notifications, unreadCount, getNotifications, getUnreadCount, markAsRead, markAllAsRead, listenForNotifications } = useNotifications()
const isOpen = ref(false)

const formattedNotifications = computed(() => {
    return notifications.value.map(notification => ({
        ...notification,
        timeAgo: formatDistanceToNow(new Date(notification.created_at), { addSuffix: true, locale: vi })
    }))
})

function toggleDropdown() {
    isOpen.value = !isOpen.value
}

function closeDropdown() {
    isOpen.value = false
}

async function refreshNotifications() {
    await getNotifications()
    await getUnreadCount()
}

function handleMarkAsRead(notificationId: number) {
    markAsRead(notificationId)
}

// Cập nhật thông báo mỗi 30 giây
let timer: number
onMounted(async () => {
    refreshNotifications()
    timer = window.setInterval(() => {
        refreshNotifications()
    }, 30000)
    
    // Kích hoạt lắng nghe real-time
    const userData = await getUserData()
    if (userData && userData.user && userData.user.id) {
        listenForNotifications(userData.user.id)
    }
})

// Dọn dẹp khi component bị huỷ
watchEffect((onCleanup) => {
    onCleanup(() => {
        clearInterval(timer)
    })
})
</script>

<template>
    <div class="notification-dropdown">
        <div class="notification-button" @click="toggleDropdown">
            <i class="bi bi-bell"></i>
            <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</span>
        </div>
        
        <div v-if="isOpen" class="notification-dropdown-content">
            <div class="notification-header">
                <h6>Thông báo</h6>
                <button v-if="unreadCount > 0" @click="markAllAsRead" class="mark-all-read">
                    Đánh dấu tất cả là đã đọc
                </button>
            </div>
            
            <div class="notification-list">
                <div v-if="formattedNotifications.length === 0" class="empty-notification">
                    Không có thông báo nào
                </div>
                
                <div 
                    v-for="notification in formattedNotifications" 
                    :key="notification.id" 
                    class="notification-item"
                    :class="{ 'unread': !notification.is_read }"
                    @click="handleMarkAsRead(notification.id)"
                >
                    <div class="notification-icon">
                        <i v-if="notification.type === 'task_assigned'" class="bi bi-clipboard-plus"></i>
                        <i v-else-if="notification.type === 'task_status_changed'" class="bi bi-arrow-repeat"></i>
                        <i v-else class="bi bi-bell"></i>
                    </div>
                    <div class="notification-content">
                        <h6>{{ notification.title }}</h6>
                        <p>{{ notification.message }}</p>
                        <small>{{ notification.timeAgo }}</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lớp phủ để đóng dropdown khi click bên ngoài -->
        <div v-if="isOpen" class="notification-overlay" @click="closeDropdown"></div>
    </div>
</template>

<style scoped>
.notification-dropdown {
    position: relative;
    display: inline-block;
}

.notification-button {
    position: relative;
    cursor: pointer;
    padding: 8px;
}

.notification-button i {
    font-size: 1.25rem;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: red;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    font-size: 0.75rem;
    text-align: center;
    line-height: 18px;
}

.notification-dropdown-content {
    position: absolute;
    top: 100%;
    right: 0;
    width: 320px;
    max-height: 400px;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    z-index: 1000;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.notification-header {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mark-all-read {
    background: none;
    border: none;
    color: #0d6efd;
    font-size: 0.8rem;
    cursor: pointer;
}

.notification-list {
    overflow-y: auto;
    max-height: 350px;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    display: flex;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f9f9f9;
}

.notification-item.unread {
    background-color: #f0f7ff;
}

.notification-icon {
    margin-right: 12px;
    font-size: 1.2rem;
    color: #0d6efd;
}

.notification-content h6 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.notification-content p {
    margin: 4px 0;
    font-size: 0.85rem;
    color: #555;
}

.notification-content small {
    color: #777;
    font-size: 0.75rem;
}

.empty-notification {
    padding: 20px;
    text-align: center;
    color: #777;
}

.notification-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999;
}
</style> 