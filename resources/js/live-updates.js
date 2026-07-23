// Периодический опрос эндпоинта и обновление UI через колбэк onSuccess.
// Мгновенные обновления обеспечивает Reverb (realtime.js): страница может
// вызвать refresh() по событию realtime:tickets. Опрос остаётся резервом.

class LiveUpdates {
    constructor(options = {}) {
        this.options = {
            refreshInterval: options.refreshInterval || 30000,
            apiEndpoint: options.apiEndpoint,
            csrfToken: options.csrfToken,
            onError: options.onError || this.defaultErrorHandler,
            onSuccess: options.onSuccess || this.defaultSuccessHandler,
            ...options,
        };

        this.refreshInterval = null;
        this.isRefreshing = false;
        this.retryCount = 0;
        this.maxRetries = 3;

        this.init();
    }

    init() {
        if (!this.options.apiEndpoint) {
            return;
        }

        this.startAutoRefresh();

        // Обновляемся, когда вкладка снова становится видимой.
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                this.refresh();
            }
        });

        window.addEventListener("beforeunload", () => {
            this.stopAutoRefresh();
        });
    }

    async refresh() {
        if (this.isRefreshing) {
            return;
        }

        this.isRefreshing = true;
        this.updateStatusIndicator("loading");

        try {
            const response = await fetch(this.options.apiEndpoint, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.options.csrfToken || "",
                },
                cache: "no-store",
                credentials: "same-origin",
            });

            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    this.handleAuthError();
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            this.retryCount = 0;
            this.options.onSuccess(data);
            this.updateStatusIndicator("success");
            this.updateLastUpdated(data.last_updated);
        } catch (error) {
            this.handleError(error);
        } finally {
            this.isRefreshing = false;
        }
    }

    handleError(error) {
        this.retryCount++;

        if (this.retryCount <= this.maxRetries) {
            setTimeout(() => {
                this.refresh();
            }, 5000 * this.retryCount);
        } else {
            this.updateStatusIndicator("error");
        }

        this.options.onError(error);
    }

    handleAuthError() {
        setTimeout(() => {
            window.location.href = "/login";
        }, 1000);
    }

    updateStatusIndicator(status) {
        const indicator = document.getElementById("status-indicator");
        if (!indicator) return;

        const statusClasses = {
            loading: "w-2 h-2 bg-green-500 rounded-full",
            success: "w-2 h-2 bg-green-500 rounded-full",
            error: "w-2 h-2 bg-red-500 rounded-full",
        };

        indicator.className = statusClasses[status] || statusClasses.success;

        if (status === "error") {
            setTimeout(() => {
                if (indicator) {
                    indicator.className = "w-2 h-2 bg-green-500 rounded-full";
                }
            }, 30000);
        }
    }

    updateLastUpdated(timestamp) {
        const lastUpdated = document.getElementById("last-updated");
        if (lastUpdated && timestamp) {
            lastUpdated.textContent = `Обновлено: ${timestamp}`;
        }
    }

    startAutoRefresh() {
        if (this.refreshInterval) {
            this.stopAutoRefresh();
        }

        this.refresh();

        this.refreshInterval = setInterval(() => {
            this.refresh();
        }, this.options.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    defaultErrorHandler() {}

    defaultSuccessHandler() {}
}

if (typeof window !== "undefined") {
    window.LiveUpdates = LiveUpdates;
}
