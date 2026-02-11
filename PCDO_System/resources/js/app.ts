import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
// Common Imports
import { Head, Link, Form } from '@inertiajs/vue3';
// Common Components
import Button from './components/ui/button/Button.vue';
import Label from './components/ui/label/Label.vue';
import Input from './components/ui/input/Input.vue';
import InputField from './components/ui/input/Input.vue';
// Common Library Components 
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuPortal, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuSeparator, DropdownMenuShortcut, DropdownMenuSub, DropdownMenuSubContent, DropdownMenuSubTrigger, DropdownMenuTrigger } from './components/ui/dropdown-menu';
import { Table, TableCaption, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Toaster } from '@/components/ui/sonner';
// Icons
import {
    CheckCircle, XCircle, CircleDashed, Search, SquarePen, ChevronRight, ChevronLeft, ChevronDown, Plus, FileUp, FileDown, Users, Building2, Handshake, Pin, ReceiptText,
    CircleDollarSign, Leaf, TriangleAlert, Check, Bell, Upload, Replace, FileText, X
} from 'lucide-vue-next';

import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger, } from '@/components/ui/alert-dialog'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
// Directives
import ClickOutside from './directives/ClickOutside';

const appName = import.meta.env.VITE_APP_NAME || 'Unknown ENV';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .directive('click-outside', ClickOutside)
            .component('Head', Head)
            .component('Link', Link)
            .component('Button', Button)
            .component('InputField', InputField)
            .component('Form', Form)
            // Table
            .component('Table', Table)
            .component('Label', Label)
            .component('Input', Input)
            .component('TableBody', TableBody)
            .component('TableCell', TableCell)
            .component('TableHead', TableHead)
            .component('TableHeader', TableHeader)
            .component('TableRow', TableRow)
            .component('TableCaption', TableCaption)
            // Dropdown Menu
            .component('DropdownMenu', DropdownMenu)
            .component('DropdownMenuCheckboxItem', DropdownMenuCheckboxItem)
            .component('DropdownMenuGroup', DropdownMenuGroup)
            .component('DropdownMenuLabel', DropdownMenuLabel)
            .component('DropdownMenuPortal', DropdownMenuPortal)
            .component('DropdownMenuRadioGroup', DropdownMenuRadioGroup)
            .component('DropdownMenuRadioItem', DropdownMenuRadioItem)
            .component('DropdownMenuSeparator', DropdownMenuSeparator)
            .component('DropdownMenuSub', DropdownMenuSub)
            .component('DropdownMenuSubContent', DropdownMenuSubContent)
            .component('DropdownMenuSubTrigger', DropdownMenuSubTrigger)
            .component('DropdownMenuShortcut', DropdownMenuShortcut)
            .component('DropdownMenuTrigger', DropdownMenuTrigger)
            .component('DropdownMenuContent', DropdownMenuContent)
            .component('DropdownMenuItem', DropdownMenuItem)
            // Sonner Toaster
            .component('Toaster', Toaster)
            // Icons
            .component('CheckCircle', CheckCircle)
            .component('XCircle', XCircle)
            .component('CircleDashed', CircleDashed)
            .component('Search', Search)
            .component('SquarePen', SquarePen)
            .component('ChevronRight', ChevronRight)
            .component('ChevronLeft', ChevronLeft)
            .component('ChevronDown', ChevronDown)
            .component('Plus', Plus)
            .component('FileUp', FileUp)
            .component('FileDown', FileDown)
            .component('Users', Users)
            .component('Building2', Building2)
            .component('Handshake', Handshake)
            .component('Pin', Pin)
            .component('ReceiptText', ReceiptText)
            .component('CircleDollarSign', CircleDollarSign)
            .component('Leaf', Leaf)
            .component('TriangleAlert', TriangleAlert)
            .component('Check', Check)
            .component('Bell', Bell)
            .component('Upload', Upload)
            .component('Replace', Replace)
            .component('FileText', FileText)
            .component('X', X)
            // Alert Dialog
            .component('AlertDialog', AlertDialog)
            .component('AlertDialogAction', AlertDialogAction)
            .component('AlertDialogCancel', AlertDialogCancel)
            .component('AlertDialogContent', AlertDialogContent)
            .component('AlertDialogDescription', AlertDialogDescription)
            .component('AlertDialogFooter', AlertDialogFooter)
            .component('AlertDialogHeader', AlertDialogHeader)
            .component('AlertDialogTitle', AlertDialogTitle)
            .component('AlertDialogTrigger', AlertDialogTrigger)
            .component('Alert', Alert)
            .component('AlertDescription', AlertDescription)
            .component('AlertTitle', AlertTitle)
            .component('Dialog', Dialog)
            .component('DialogContent', DialogContent)
            .component('DialogHeader', DialogHeader)
            .component('DialogTitle', DialogTitle)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
