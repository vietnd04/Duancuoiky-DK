import { ref } from 'vue'
import { makeHttpReq } from '../../../../helper/makeHttpReq'
import { showErrorResponse } from '../../../../helper/util'
import { successMsg } from '../../../../helper/toast-notification'

export type NotificationType = {
    id: number
    user_id: number
    task_id: number | null
    title: string
    message: string
    type: string
    is_read: boolean
    created_at: string
    updated_at: string
}

export type NotificationsResponse = {
    notifications: NotificationType[]
}

export function useNotifications() {
    const loading = ref(false)
    const notifications = ref<NotificationType[]>([])
    const unreadCount = ref(0)

    async function getNotifications() {
        try {
            loading.value = true
            const response = await makeHttpReq<undefined, NotificationsResponse>('notifications', 'GET')
            loading.value = false
            notifications.value = response.notifications
        } catch (error) {
            loading.value = false
            showErrorResponse(error)
        }
    }

    async function getUnreadCount() {
        try {
            const response = await makeHttpReq<undefined, { count: number }>('notifications/unread-count', 'GET')
            unreadCount.value = response.count
        } catch (error) {
            showErrorResponse(error)
        }
    }

    async function markAsRead(notificationId: number) {
        try {
            loading.value = true
            await makeHttpReq<{ notification_id: number }, { message: string }>(
                'notifications/mark-read',
                'POST',
                { notification_id: notificationId }
            )
            loading.value = false
            
            // Cập nhật trạng thái thông báo tại client
            const notification = notifications.value.find(n => n.id === notificationId)
            if (notification) {
                notification.is_read = true
            }
            
            // Cập nhật số lượng chưa đọc
            await getUnreadCount()
        } catch (error) {
            loading.value = false
            showErrorResponse(error)
        }
    }

    async function markAllAsRead() {
        try {
            loading.value = true
            await makeHttpReq<undefined, { message: string }>('notifications/mark-all-read', 'POST')
            loading.value = false
            
            // Cập nhật trạng thái tất cả thông báo tại client
            notifications.value.forEach(notification => {
                notification.is_read = true
            })
            
            // Đặt lại số lượng chưa đọc về 0
            unreadCount.value = 0
            
            successMsg('Đã đánh dấu tất cả thông báo là đã đọc')
        } catch (error) {
            loading.value = false
            showErrorResponse(error)
        }
    }

    function listenForNotifications(userId: number) {
        if (window.Echo) {
            window.Echo.private(`notifications.${userId}`)
                .listen('notification.new', (data: { notification: NotificationType }) => {
                    // Thêm thông báo mới vào đầu danh sách
                    notifications.value.unshift(data.notification)
                    
                    // Tăng số lượng thông báo chưa đọc
                    unreadCount.value++
                    
                    // Hiển thị thông báo
                    successMsg('Bạn có thông báo mới')
                })
        }
    }

    return {
        loading,
        notifications,
        unreadCount,
        getNotifications,
        getUnreadCount,
        markAsRead,
        markAllAsRead,
        listenForNotifications
    }
} 