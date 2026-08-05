import axios from "axios";

const apiClient = axios.create({
  baseURL:
    import.meta.env.VITE_API_BASE_URL || "http://localhost:8020/api",
  timeout: 15000,
  headers: {
    Accept: "application/json",
  },
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const message =
      error.response?.data?.message ||
      error.message ||
      "Không thể kết nối tới máy chủ.";

    return Promise.reject({
      originalError: error,
      status: error.response?.status,
      message,
      validationErrors: error.response?.data?.errors || {},
    });
  }
);

export default apiClient;
