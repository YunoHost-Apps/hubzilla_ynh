(ns generate-hooks-index.core
  (:require [clojure.string :as str]
            [hiccup.core :as h]
            [taoensso.timbre :as log]
            [clojure.java.shell :as sh])
  (:gen-class))

(log/set-level! :info)

(defn clean-fn-arg
  [s]
  (-> s
      (str/replace #"'" "")
      (str/replace #"\"" "")
      str/trim))

(defn check-fn-args
  [xs]
  (when (-> xs first empty?)
    (throw (Exception. "empty function")))
  (map clean-fn-arg xs))


(defn get-fn-arg
  [s]
  (->> s
       (re-matches #".*call_hooks\((.+)\,(.*)\).*")
       rest
       check-fn-args))



(defn fix-path
  [path file]
  (str/replace file path ""))


(defn show-hooks
  [path]
  (for [s (-> (sh/sh "rgrep" "call_hooks" path)
              :out
              str/split-lines)
        :let [[file hook]   (str/split s #"\t*:")]]
    (try
      (-> (zipmap [:function :arg] (get-fn-arg hook))
          (assoc  :file (fix-path path file)))
      (catch Exception e
        (log/debug e s file hook)))))



(defn hiccupy
  [path]
  [:div
   [:h3 "Hooks"]
   [:table
    [:tr (map #(vector :td %) ["Function" "Source File" "Arg"])]
    (for [{:keys [function arg file]} 
          (->> path
               show-hooks
               (sort-by :function))]
      [:tr  (map #(vector :td (h/h %)) [function file arg])])]
   [:p "Generated " (-> (java.util.Date.) str)]])


(defn make-hook-docs
  [path-to-hubzillla]
  (->> path-to-hubzillla
       hiccupy
       h/html
       (spit (str path-to-hubzillla "doc/hooks.html"))))


(defn -main
  [& args]
  (log/info "Starting..")
  (make-hook-docs (str (System/getProperty "user.dir") "/../../"))
  (log/info "Done!")
  (System/exit 0))



