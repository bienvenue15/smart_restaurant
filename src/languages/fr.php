<?php
/**
 * French Translations (Français)
 */

return [
    // Common
    'app_name' => 'Restaurant Intelligent',
    'welcome' => 'Bienvenue',
    'hello' => 'Bonjour',
    'loading' => 'Chargement...',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'close' => 'Fermer',
    'confirm' => 'Confirmer',
    'yes' => 'Oui',
    'no' => 'Non',
    'search' => 'Rechercher',
    'filter' => 'Filtrer',
    'all' => 'Tout',
    'actions' => 'Actions',
    'view' => 'Voir',
    'print' => 'Imprimer',
    'download' => 'Télécharger',
    'refresh' => 'Actualiser',
    'back' => 'Retour',
    'next' => 'Suivant',
    'previous' => 'Précédent',
    'select' => 'Sélectionner',
    'total' => 'Total',
    'subtotal' => 'Sous-total',
    'logout' => 'Déconnexion',

    // Navigation
    'nav' => [
        'dashboard' => 'Tableau de bord',
        'menu' => 'Menu',
        'orders' => 'Commandes',
        'tables' => 'Tables',
        'staff' => 'Personnel',
        'reports' => 'Rapports',
        'settings' => 'Paramètres',
        'waiter_calls' => 'Appels serveur',
        'cash_register' => 'Caisse enregistreuse',
        'liabilities' => 'Responsabilités serveur',
    ],

    // Menu
    'menu' => [
        'title' => 'Notre Menu',
        'categories' => 'Catégories',
        'items' => 'Articles du menu',
        'add_item' => 'Ajouter un article',
        'price' => 'Prix',
        'available' => 'Disponible',
        'unavailable' => 'Indisponible',
        'special' => 'Spécial',
        'new' => 'Nouveau',
        'popular' => 'Populaire',
        'add_to_cart' => 'Ajouter au panier',
        'add_to_order' => 'Ajouter à la commande',
        'view_cart' => 'Voir le panier',
        'search_menu' => 'Rechercher dans le menu...',
        'unavailable_contact_staff' => 'Le menu est actuellement indisponible. Veuillez contacter le personnel.',
        'welcome_to' => 'Bienvenue à',
        'our_restaurant' => 'Notre Restaurant',
        'browse_order_hint' => 'Parcourez notre menu et passez votre commande directement depuis votre téléphone',
        'filter_by' => 'Filtrer par',
        'all_items' => 'Tous les articles',
    ],

    // Orders
    'orders' => [
        'new_order' => 'Nouvelle commande',
        'order_number' => 'Commande #',
        'table' => 'Table',
        'status' => 'Statut',
        'total_amount' => 'Montant total',
        'created_at' => 'Créé le',
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'ready' => 'Prête',
        'served' => 'Servie',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'place_order' => 'Passer la commande',
        'cancel_order' => 'Annuler la commande',
        'track_order' => 'Suivre la commande',
        'your_orders' => 'Vos commandes',
        'your_order' => 'Votre commande',
        'active_orders' => 'Commandes actives',
        'order_details' => 'Détails de la commande',
        'order_empty' => 'Votre commande est vide',
        'order_empty_hint' => 'Commencez à ajouter des articles du menu',
        'special_instructions' => 'Instructions spéciales (optionnel)...',
        'clear_order' => 'Vider la commande',
        'order_placed_success' => 'Commande passée avec succès!',
        'your_order_number' => 'Votre numéro de commande est',
        'order_sent_to_kitchen' => 'Votre commande a été envoyée à la cuisine. Vous serez notifié quand elle sera prête.',
    ],

    // Tables
    'tables' => [
        'table_number' => 'Numéro de table',
        'capacity' => 'Capacité',
        'occupied' => 'Occupée',
        'available' => 'Disponible',
        'reserved' => 'Réservée',
        'assign_waiter' => 'Assigner un serveur',
    ],

    // Staff
    'staff' => [
        'login' => 'Connexion personnel',
        'username' => 'Nom d\'utilisateur',
        'password' => 'Mot de passe',
        'role' => 'Rôle',
        'admin' => 'Administrateur',
        'manager' => 'Gestionnaire',
        'waiter' => 'Serveur',
        'kitchen' => 'Personnel de cuisine',
        'cashier' => 'Caissier',
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    // Waiter Calls
    'calls' => [
        'request_waiter' => 'Demander un serveur',
        'request_bill' => 'Demander l\'addition',
        'call_waiter' => 'Appeler un serveur',
        'pending_calls' => 'Appels en attente',
        'priority' => 'Priorité',
        'high' => 'Haute',
        'medium' => 'Moyenne',
        'low' => 'Basse',
    ],

    // Notifications
    'notifications' => [
        'new_order' => 'Nouvelle commande',
        'order_ready' => 'Commande prête',
        'waiter_call' => 'Appel serveur',
        'no_notifications' => 'Aucune notification',
        'mark_read' => 'Marquer comme lu',
        'mark_all_read' => 'Tout marquer comme lu',
    ],

    // Time
    'time' => [
        'just_now' => 'À l\'instant',
        'mins_ago' => 'Il y a :count min',
        'hours_ago' => 'Il y a :count heures',
        'days_ago' => 'Il y a :count jours',
        'yesterday' => 'Hier',
    ],

    // Messages
    'messages' => [
        'success' => 'Succès!',
        'error' => 'Erreur!',
        'warning' => 'Attention!',
        'confirm_action' => 'Êtes-vous sûr?',
        'order_placed' => 'Commande passée avec succès',
        'order_cancelled' => 'Commande annulée avec succès',
        'payment_received' => 'Paiement reçu',
        'session_expired' => 'Session expirée. Veuillez vous reconnecter.',
        'added_to_order' => 'ajouté à la commande',
        'item_not_found' => 'Article non trouvé',
        'table_not_found' => 'Informations de table introuvables',
        'cart_empty' => 'Votre panier est vide',
        'network_error' => 'Erreur réseau. Veuillez réessayer.',
        'order_failed' => 'Échec de la commande',
        'cancel_failed' => 'Échec de l\'annulation',
    ],

    // Waiter Calls
    'calls' => [
        'call_waiter' => 'Appeler un serveur',
        'request_type' => 'Type de demande',
        'need_assistance' => 'Besoin d\'aide',
        'ready_to_order' => 'Prêt à commander',
        'request_bill' => 'Demander l\'addition',
        'complaint' => 'Réclamation',
        'other' => 'Autre',
        'message_optional' => 'Message (optionnel)',
        'additional_details' => 'Détails supplémentaires...',
        'priority' => 'Priorité',
        'normal' => 'Normal',
        'urgent' => 'Urgent',
    ],

    // Cookies
    'cookies' => [
        'title' => 'Nous respectons votre vie privée',
        'message' => 'Nous utilisons des cookies pour améliorer votre expérience de commande, mémoriser vos préférences et analyser l\'utilisation. En cliquant sur "Accepter", vous consentez à notre utilisation des cookies.',
        'learn_more' => 'En savoir plus',
        'accept' => 'Accepter tous les cookies',
        'decline' => 'Refuser',
    ],

    // Customer
    'customer' => [
        'scan_qr' => 'Scanner le code QR',
        'table_locked' => 'Table verrouillée par un autre appareil',
        'request_bill' => 'Demander l\'addition',
        'order_tracking' => 'Suivi de commande',
    ],
    // Order Tracking
    'tracking' => [
        'title' => 'Suivi de commande',
        'item_status' => 'État des articles',
        'pending_desc' => 'Commande reçue, en attente de la cuisine',
        'confirmed_desc' => 'La cuisine a confirmé votre commande',
        'preparing_desc' => 'Votre nourriture est en préparation',
        'ready_desc' => 'La commande est prête à être servie',
        'served_desc' => 'Bon appétit!',
        'complete' => 'Terminé',
    ],
    // Liabilities
    'liabilities' => [
        'title' => 'Responsabilités serveur',
        'active' => 'Actif',
        'cleared' => 'Effacé',
        'loss' => 'Perte',
        'waived' => 'Annulé',
        'mark_as_loss' => 'Marquer comme perte',
        'waive' => 'Annuler',
        'reason' => 'Raison',
        'performance' => 'Performance',
        'excellent' => 'Excellent',
        'good' => 'Bien',
        'fair' => 'Correct',
        'needs_attention' => 'Nécessite attention',
    ],

    // Reports
    'reports' => [
        'sales' => 'Rapport des ventes',
        'daily' => 'Quotidien',
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuel',
        'custom' => 'Personnalisé',
        'total_sales' => 'Ventes totales',
        'total_orders' => 'Commandes totales',
        'average_order' => 'Commande moyenne',
    ],
];
