import axios from 'axios';
import {
    createIcons, ArrowDownToLine, ArrowLeft, ArrowRight, BadgeDollarSign, Bell, ChartNoAxesCombined, Check, ChevronDown, ClipboardList,
    Eye, HandCoins, Landmark, LayoutDashboard, ListFilter, LogOut, Menu, Package, PackageCheck, PackagePlus, Pencil, Plus,
    Receipt, ReceiptText, RotateCcw, Save, ScanSearch, Search, Send, ShoppingCart, Store, Trash2, Truck,
    TrendingUp, Undo2, UsersRound, WalletCards, Warehouse, X,
} from 'lucide';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const icons = {ArrowDownToLine,ArrowLeft,ArrowRight,BadgeDollarSign,Bell,ChartNoAxesCombined,Check,ChevronDown,ClipboardList,Eye,HandCoins,Landmark,LayoutDashboard,ListFilter,LogOut,Menu,Package,PackageCheck,PackagePlus,Pencil,Plus,Receipt,ReceiptText,RotateCcw,Save,ScanSearch,Search,Send,ShoppingCart,Store,Trash2,TrendingUp,Truck,Undo2,UsersRound,WalletCards,Warehouse,X};
const renderIcons = () => createIcons({icons, attrs: {'stroke-width': 2}});

document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('livewire:navigated', renderIcons);
document.addEventListener('livewire:init', () => Livewire.hook('morph.updated', renderIcons));

const registerPwa = async () => {
    if (!document.querySelector('link[rel="manifest"]')) {
        const manifest = document.createElement('link');
        manifest.rel = 'manifest';
        manifest.href = '/manifest.webmanifest';
        document.head.appendChild(manifest);
    }
    return 'serviceWorker' in navigator ? navigator.serviceWorker.register('/sw.js') : null;
};

const showPendingNotifications = async () => {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    const registration = await registerPwa();
    if (!registration) return;
    const response = await fetch('/notifications/feed', {credentials: 'same-origin'});
    if (!response.ok) return;
    const {notifications = []} = await response.json();
    const shown = new Set(JSON.parse(localStorage.getItem('tilly-shown-notifications') || '[]'));

    for (const notification of notifications) {
        if (shown.has(notification.key)) continue;
        await registration.showNotification(notification.title, {body: notification.body, icon: '/pwa-icon.svg', badge: '/pwa-icon.svg', tag: notification.key, data: {url: notification.url}});
        shown.add(notification.key);
    }
    localStorage.setItem('tilly-shown-notifications', JSON.stringify([...shown].slice(-200)));
};

window.enablePwaNotifications = async (button) => {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        alert('Notifications are not supported by this browser.');
        return;
    }
    const original = button.innerHTML;
    button.disabled = true;
    button.textContent = 'Enabling...';
    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') throw new Error('Notification permission was not allowed.');
        await registerPwa();
        await showPendingNotifications();
        button.textContent = 'Notifications allowed';
    } catch (error) {
        alert(error.message || 'Could not enable notifications.');
        button.innerHTML = original;
        renderIcons();
    } finally {
        button.disabled = false;
    }
};

document.addEventListener('DOMContentLoaded', () => { registerPwa(); showPendingNotifications(); });
document.addEventListener('livewire:navigated', showPendingNotifications);
setInterval(showPendingNotifications, 5 * 60 * 1000);

window.shareReceipt = async (button) => {
    const url = button.dataset.url;
    const filename = button.dataset.filename;
    const original = button.innerHTML;
    button.disabled = true;
    button.textContent = 'Preparing PDF...';

    try {
        const response = await fetch(url, {credentials: 'same-origin'});
        if (!response.ok) throw new Error('Could not create the receipt.');
        const blob = await response.blob();
        const file = new File([blob], filename, {type: 'application/pdf'});

        if (navigator.share && (!navigator.canShare || navigator.canShare({files: [file]}))) {
            await navigator.share({title: 'Sales receipt', text: 'Receipt attached.', files: [file]});
        } else {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
            alert('Your browser cannot attach files directly. The PDF was downloaded; attach it in WhatsApp.');
        }
    } catch (error) {
        if (error.name !== 'AbortError') alert(error.message || 'Unable to share the receipt.');
    } finally {
        button.disabled = false;
        button.innerHTML = original;
        renderIcons();
    }
};
