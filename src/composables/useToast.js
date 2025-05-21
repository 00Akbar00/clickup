/**
 * No-op version of the toast composable
 * All toast functionality has been removed to improve performance
 */
export function useToast() {
  // No-op functions
  const showToast = () => {}
  const createAdvancedToast = () => ({ close: () => {} })
  const showSuccess = () => {}
  const showError = () => {}
  const showWarning = () => {}
  const showInfo = () => {}
  const clearToast = () => {}

  return {
    showToast,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    clearToast,
    createAdvancedToast
  }
}